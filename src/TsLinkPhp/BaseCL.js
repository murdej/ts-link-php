var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
export class BaseCL {
    constructor(url = "") {
        this.context = {};
        this.url = "";
        this.onPrepareRequest = null;
        this.onLoading = null;
        this.onLoaded = null;
        this.onError = null;
        this.url = url;
    }
    // @ts-ignore
    callMethod(methodName, args /* : IArguments*/, callOpts = { rawResult: false }, newDataType = null) {
        return __awaiter(this, void 0, void 0, function* () {
            const srcData = {
                name: methodName,
                context: this.context,
                pars: [...args]
            };
            const ev = {
                url: this.url,
                requestInit: {
                    method: 'POST',
                    body: JSON.stringify(srcData),
                    headers: {
                        'Content-Type': 'application/json',
                    },
                }
            };
            if (this.onPrepareRequest)
                this.onPrepareRequest(ev, srcData);
            let loadingHandle = undefined;
            let response = undefined;
            if (this.onLoading)
                loadingHandle = this.onLoading(srcData);
            try {
                const fetchRes = yield fetch(ev.url, ev.requestInit);
                if (fetchRes.headers["Content-Type"] === "octed/stream") {
                    response = yield fetchRes.blob();
                    return response;
                }
                else {
                    response = yield fetchRes.json();
                    if (response.status == "ok") {
                        if (this.onLoaded)
                            this.onLoaded(loadingHandle, response);
                        if (newDataType)
                            response.response = new newDataType(response.response);
                        return response.response;
                    }
                    else {
                        throw new Error(response.exception);
                    }
                }
            }
            catch (exc) {
                if (this.onLoaded)
                    this.onLoaded(loadingHandle, response);
                if (this.onError)
                    this.onError(loadingHandle, exc);
                throw exc;
            }
            finally {
                if (this.onLoaded)
                    this.onLoaded(loadingHandle, response);
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
        });
    }
}
