
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


		/* const ajax = new Ajax(this.url);
		// if (this.ajaxXMLHttpRequestClass) ajax.XMLHttpRequestClass  = this.ajaxXMLHttpRequestClass;
		// ajax.requestContentType = "application/json";
		const srcData = {
			name: methodName,
			context: this.context,
			pars: [...args]
		};
		ajax.setData(srcData);
		ajax.responseType = callOpts.rawResult ? "arraybuffer" : "json";
		const dispatchError = (err : any) => {

		}
		return new Promise((resolve, reject) => {
			let loadingHandle : any;
			if (this.onLoading) loadingHandle = this.onLoading(srcData);
			ajax.promise()
				.then((response : CallMethodResponse|string|any) => {
					if (ajax.responseHeaders["Content-Type"] === "octed/stream")
					{
						resolve(response);
					} else {
						if (typeof response === "string") response = JSON.parse(response) as CallMethodResponse;
						if (response.status == "ok")
						{
							if (this.onLoaded) this.onLoaded(loadingHandle, response);
							if (newDataType) response.response = new newDataType(response.response);
							resolve(response.response);
						}
						else
						{
							if (this.onLoaded) this.onLoaded(loadingHandle, response);
							if (reject) reject(response.exception);
							else {
								// alert("Chyba aplikace: " + response.Exception.Message);
								if (this.onError) this.onError(loadingHandle, response.exception);
								throw new Error(response.exception);
							}
						}
					}
				}).catch((exc) => {
					if (this.onLoaded) this.onLoaded(loadingHandle, exc);
					if (reject) reject(exc);
					else {
						if (this.onError) this.onError(loadingHandle, exc);
						throw new Error(exc);
					}
				}
			);
		}); */
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
