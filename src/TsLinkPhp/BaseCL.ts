
export type EventMethodCallData = {
	name: string,
	context: any,
	pars: any[],
	uploadArgs: number[],
}

export type EventError = {
	methodArgs: any[],
	rawResponse: string|object|null,
	error: Error|any|null,
}

export type BatchConfig = {
	sleepTimeout: number,
	maxTimeout: number,
	maxRequests: number,
}

type BatchQueueItem = {
	id: number,
	srcData: EventMethodCallData,
	uploads: Record<string, File>,
	newDataType: (new(data:any)=>any) | null,
	resolve: (value: any) => void,
	reject: (reason: any) => void,
}

export class BaseCL {
	public context : any = {};

	public url : string = "";

	public onPrepareRequest : ((ev: ClCallEvent, data : EventMethodCallData) => any)|null = null;

	public onLoading : ((data : EventMethodCallData) => any)|null = null;

	public onLoaded : ((handle : any, data : any)=>void)|null = null;

	public onError : ((handle : any, ev: EventError)=>void)|null = null;

    public dataFetcher: (input: RequestInfo | URL, init: RequestInit, ev: ClCallEvent) => Promise<DataFetcherResponse> = this.defaultDataFetcher;

    protected batchMode : boolean = false;

    protected batchConfig : BatchConfig|null = null;

    protected batchQueue : BatchQueueItem[] = [];

    protected batchNextId : number = 1;

    protected batchSleepTimer : ReturnType<typeof setTimeout>|null = null;

    protected batchMaxTimer : ReturnType<typeof setTimeout>|null = null;

    public async defaultDataFetcher(input: RequestInfo | URL, init: RequestInit, ev: ClCallEvent): Promise<DataFetcherResponse>
    {
        const fetchRes = await fetch(input, init);
		const contentType = fetchRes.headers.get("Content-Type") ?? 'text/plain';
        const isBlob = contentType === "octed/stream";

        const res: DataFetcherResponse = {
            isBlob,
            response: isBlob
				? await fetchRes.blob()
				: (contentType === 'application/json' ? await fetchRes.json() : await fetchRes.text()),
			contentType,
			status: fetchRes.status,
			statusText: fetchRes.statusText,
			ok: fetchRes.ok,
        }
		//console.log('fetch res', res);
		return res;
    }

	// @ts-ignore
	protected buildCallData(methodName : string, args : any) : { srcData: EventMethodCallData, uploads: Record<string, File> } {
		const newArgs= [];
		const uploads: Record<string, File> = {};
		let c = 0;
		let i = 0;
		const uploadArgs = [];
		for (const arg of args) {
			if (arg instanceof File) {
				uploads[c] = arg;
				newArgs.push(c);
				c++;
				uploadArgs.push(i);
			} else if (typeof FileList !== 'undefined' && arg instanceof FileList) {
				const newArg: any[] = [];
				// @ts-ignore
				for (const file of arg) {
					uploads[c] = file;
					newArg.push(c);
					c++;
					uploadArgs.push(i);
				}
				newArgs.push(newArg);
			} else {
				newArgs.push(arg);
			}
			i++;
		}

		return {
			srcData: {
				name: methodName,
				context: this.context,
				pars: newArgs,
				uploadArgs,
			},
			uploads,
		};
	}

	protected async sendSingle(srcData: EventMethodCallData, uploads: Record<string, File>, newDataType: (new(data:any)=>any)|null, args: any) : Promise<any> {
		let contentType: string|null;
		let postData: string|FormData;
		if (Object.keys(uploads).length > 0) {
			contentType = null;
			postData = new FormData();
			postData.append('request', JSON.stringify(srcData));
			for (const k in uploads) {
				// @ts-ignore
				postData.append(k, uploads[k]);
			}
		} else {
			contentType = 'application/json';
			postData = JSON.stringify(srcData);
		}

		const ev: ClCallEvent = {
			url: this.url,
			requestInit: {
				method: 'POST',
				body: postData,
				headers: contentType ? { 'Content-Type': contentType } : {},
			}
		}

		if (this.onPrepareRequest) this.onPrepareRequest(ev, srcData);

		let loadingHandle : any = undefined;
		let response : any = undefined;

		if (this.onLoading) loadingHandle = this.onLoading(srcData);
		const eventError: EventError = {
			methodArgs: args,
			rawResponse: null,
			error: null,
		};

		try {
			const fetchRes = await this.dataFetcher(ev.url, ev.requestInit, ev);
			//console.log('fetchRes', fetchRes);
			if (fetchRes.isBlob) {
				return fetchRes.response;
			}
			else
			{
				response = fetchRes.response;

				if (response.status == "ok")
				{
					if (newDataType) response.response = new newDataType(response.response);
					return response.response;
				}
				else
				{
					throw new Error(response.exception);
				}
			}
		} catch (exc: any) {
			eventError.error = exc;
			if (this.onError) this.onError(loadingHandle, eventError);
			throw exc;
		} finally {
			if (this.onLoaded) this.onLoaded(loadingHandle, response);
		}
	}

	protected queueBatchCall(srcData: EventMethodCallData, uploads: Record<string, File>, newDataType: (new(data:any)=>any)|null, resolve: (value:any)=>void, reject: (reason:any)=>void) : void {
		const id = this.batchNextId++;
		this.batchQueue.push({ id, srcData, uploads, newDataType, resolve, reject });

		if (this.batchConfig) {
			const config = this.batchConfig;

			if (this.batchSleepTimer) clearTimeout(this.batchSleepTimer);
			this.batchSleepTimer = setTimeout(() => this.send(), config.sleepTimeout);

			if (this.batchQueue.length === 1) {
				this.batchMaxTimer = setTimeout(() => this.send(), config.maxTimeout);
			}

			if (this.batchQueue.length >= config.maxRequests) {
				this.send();
			}
		}
	}

	/**
	 * Turns on batch mode. With a callback, runs it, sends the queued batch and returns the callback's
	 * return value (typically an array of the still-pending call promises, for Promise.all). Without a
	 * callback, just enables batch mode and returns this - the caller must call .send() themselves.
	 */
	public useBatch(callback: ((tl: this) => any)|null = null) : any {
		this.batchMode = true;
		this.batchConfig = null;

		if (callback) {
			const result = callback(this);
			this.send();
			return result;
		}

		return this;
	}

	/**
	 * Returns a clone of this instance with batch mode on and auto-flush governed by config
	 * (sleepTimeout/maxTimeout/maxRequests). Passing null returns a clone with batching off.
	 */
	public useBatchAuto(config: BatchConfig|null) : this {
		const clone = Object.assign(Object.create(Object.getPrototypeOf(this)), this) as this;
		clone.batchMode = config !== null;
		clone.batchConfig = config;
		clone.batchQueue = [];
		clone.batchNextId = 1;
		clone.batchSleepTimer = null;
		clone.batchMaxTimer = null;
		return clone;
	}

	/** Flushes the current batch queue as a single request. */
	public async send() : Promise<void> {
		if (this.batchSleepTimer) { clearTimeout(this.batchSleepTimer); this.batchSleepTimer = null; }
		if (this.batchMaxTimer) { clearTimeout(this.batchMaxTimer); this.batchMaxTimer = null; }

		const queue = this.batchQueue;
		this.batchQueue = [];
		if (queue.length === 0) return;

		const uploads: Record<string, File> = {};
		for (const item of queue) {
			for (const k in item.uploads) {
				uploads[`${item.id}_${k}`] = item.uploads[k];
			}
		}

		const batch = queue.map(item => ({ id: item.id, ...item.srcData }));

		let contentType: string|null;
		let postData: string|FormData;
		if (Object.keys(uploads).length > 0) {
			contentType = null;
			postData = new FormData();
			postData.append('request', JSON.stringify({ batch }));
			for (const k in uploads) {
				// @ts-ignore
				postData.append(k, uploads[k]);
			}
		} else {
			contentType = 'application/json';
			postData = JSON.stringify({ batch });
		}

		const ev: ClCallEvent = {
			url: this.url,
			requestInit: {
				method: 'POST',
				body: postData,
				headers: contentType ? { 'Content-Type': contentType } : {},
			}
		}

		try {
			const fetchRes = await this.dataFetcher(ev.url, ev.requestInit, ev);
			const items: any[] = fetchRes.response?.batch ?? [];
			const byId = new Map(items.map((item: any) => [item.id, item]));

			for (const q of queue) {
				const item = byId.get(q.id);
				if (!item) {
					q.reject(new Error('Missing response for batch item id ' + q.id));
				} else if (item.status === "ok") {
					q.resolve(q.newDataType ? new q.newDataType(item.response) : item.response);
				} else {
					q.reject(new Error(item.exception));
				}
			}
		} catch (exc: any) {
			for (const q of queue) q.reject(exc);
		}
	}

	// @ts-ignore
	protected async callMethod(methodName : string, args : any/* : IArguments*/, callOpts : CallOpts = { rawResult: false }, newDataType: new(data:any)=>any = null) : Promise<any> {
		const { srcData, uploads } = this.buildCallData(methodName, args);

		if (this.batchMode) {
			return new Promise((resolve, reject) => {
				this.queueBatchCall(srcData, uploads, newDataType, resolve, reject);
			});
		}

		return this.sendSingle(srcData, uploads, newDataType, args);
	}

	constructor(url: string = "") {
		this.url = url;
	}
}

export type CallMethodResponse = {
	response?: any,
	status: "ok"|"failed"|string,
	exception?: {
		Detail: string,
		Message: string
	}|any
}

export type CallOpts = {
	rawResult : boolean
}

export type ClCallEvent = {
	url: string,
	requestInit: RequestInit,
}

export type DataFetcherResponse = {
	ok: boolean,
    response : any,
    isBlob?: boolean,
	status?: number,
	statusText?: string,
	contentType?: string,
}
