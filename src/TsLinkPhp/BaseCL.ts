
export class BaseCL {
	public context : any = {};

	public url : string = "";

	public onPrepareRequest : ((ev: ClCallEvent, data : any) => any)|null = null;

	public onLoading : ((data : any) => any)|null = null;

	public onLoaded : ((handle : any, data : any)=>void)|null = null;

	public onError : ((handle : any, error : any)=>void)|null = null;

	// @ts-ignore
	protected async callMethod(methodName : string, args : any/* : IArguments*/, callOpts : CallOpts = { rawResult: false }, newDataType = null) : Promise<any> {

		const srcData = {
			name: methodName,
			context: this.context,
			pars: [...args]
		};

		const ev: ClCallEvent = {
			url: this.url,
			requestInit: {
				method: 'POST', // Metoda požadavku
				body: JSON.stringify(srcData),
				headers: {
					'Content-Type': 'application/json',
				},
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
