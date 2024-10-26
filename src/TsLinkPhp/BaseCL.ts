
export class BaseCL {
	public context : any = {};

	public foo = 42;

	public url : string = "";

	public onPrepareRequest : ((ev: ClCallEvent, data : any) => any)|null = null;

	public onLoading : ((data : any) => any)|null = null;

	public onLoaded : ((handle : any, data : any)=>void)|null = null;

	public onError : ((handle : any, error : any)=>void)|null = null;

	// @ts-ignore
	protected async callMethod(methodName : string, args : any/* : IArguments*/, callOpts : CallOpts = { rawResult: false }, newDataType = null) : Promise<any> {

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
			} else if (arg instanceof FileList) {
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

		const srcData = {
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

		try {
			const fetchRes = await fetch(ev.url, ev.requestInit);

			if (fetchRes.headers["Content-Type"] === "octed/stream") {
				response = await fetchRes.blob();
				return response;
			}
			else
			{
				response = await fetchRes.json();

				if (response.status == "ok")
				{
					if (this.onLoaded) this.onLoaded(loadingHandle, response);
					if (newDataType) response.response = new newDataType(response.response);
					return response.response;
				}
				else
				{
					throw new Error(response.exception);
				}
			}
		} catch (exc) {
			if (this.onLoaded) this.onLoaded(loadingHandle, response);
			if (this.onError) this.onError(loadingHandle, exc);
			throw exc;
		} finally {
			if (this.onLoaded) this.onLoaded(loadingHandle, response);
		}
	}

	constructor(url: string = "") {
		this.url = url;
	}


	/* static getInstance(): self
	{
		if (!this.instance) {
			this.instance = new this();
		}
		return this.instance;
	} */
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
