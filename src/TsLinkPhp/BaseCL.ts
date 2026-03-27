
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

export class BaseCL {
	public context : any = {};

	public url : string = "";

	public onPrepareRequest : ((ev: ClCallEvent, data : EventMethodCallData) => any)|null = null;

	public onLoading : ((data : EventMethodCallData) => any)|null = null;

	public onLoaded : ((handle : any, data : any)=>void)|null = null;

	public onError : ((handle : any, ev: EventError)=>void)|null = null;

    public dataFetcher: (input: RequestInfo | URL, init: RequestInit, ev: ClCallEvent) => Promise<DataFetcherResponse> = this.defaultDataFetcher;

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
	protected async callMethod(methodName : string, args : any/* : IArguments*/, callOpts : CallOpts = { rawResult: false }, newDataType: new(data:any)=>any = null) : Promise<any> {

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

		const srcData: EventMethodCallData = {
			name: methodName,
			context: this.context,
			pars: newArgs,
			uploadArgs,
		};

		let contentType: string|null;
		let postData: string|FormData;
		if (uploadArgs.length > 0) {
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
