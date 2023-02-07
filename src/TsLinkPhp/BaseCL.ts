
export class BaseCL {
	public context : any = {};

	public url : string = "";

	public onLoading : ((data : any) => any)|null = null;

	public onLoaded : ((handle : any, data : any)=>void)|null = null;

	public onError : ((handle : any, error : any)=>void)|null = null;

	// public useMethod: UseLibrary|null = null;

	protected callMethod(methodName : string, args : any/* : IArguments*/, callOpts : CallOpts = { rawResult: false }, newDataType = null) : Promise<any> {
		const ajax = new Ajax(this.url);
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
		});
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
