"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.BaseCL = void 0;
var BaseCL = /** @class */ (function () {
    function BaseCL(url) {
        if (url === void 0) { url = ""; }
        this.context = {};
        this.foo = 42;
        this.url = "";
        this.onPrepareRequest = null;
        this.onLoading = null;
        this.onLoaded = null;
        this.onError = null;
        this.url = url;
    }
    // @ts-ignore
    BaseCL.prototype.callMethod = function (methodName_1, args_1) {
        return __awaiter(this, arguments, void 0, function (methodName, args /* : IArguments*/, callOpts, newDataType) {
            var newArgs, uploads, c, i, uploadArgs, _i, args_2, arg, newArg, _a, arg_1, file, srcData, contentType, postData, k, ev, loadingHandle, response, fetchRes, exc_1;
            if (callOpts === void 0) { callOpts = { rawResult: false }; }
            if (newDataType === void 0) { newDataType = null; }
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        newArgs = [];
                        uploads = {};
                        c = 0;
                        i = 0;
                        uploadArgs = [];
                        for (_i = 0, args_2 = args; _i < args_2.length; _i++) {
                            arg = args_2[_i];
                            if (arg instanceof File) {
                                uploads[c] = arg;
                                newArgs.push(c);
                                c++;
                                uploadArgs.push(i);
                            }
                            else if (arg instanceof FileList) {
                                newArg = [];
                                // @ts-ignore
                                for (_a = 0, arg_1 = arg; _a < arg_1.length; _a++) {
                                    file = arg_1[_a];
                                    uploads[c] = file;
                                    newArgs.push(c);
                                    c++;
                                    uploadArgs.push(i);
                                }
                                newArgs.push(newArg);
                            }
                            i++;
                        }
                        srcData = {
                            name: methodName,
                            context: this.context,
                            pars: __spreadArray([], newArgs, true)
                        };
                        if (uploadArgs.length > 0) {
                            contentType = 'multipart/form-data';
                            postData = new FormData();
                            postData.append('request', JSON.stringify(newArgs));
                            for (k in uploads) {
                                postData.append(k, uploads[k]);
                            }
                        }
                        else {
                            contentType = 'application/json';
                            postData = JSON.stringify(srcData);
                        }
                        ev = {
                            url: this.url,
                            requestInit: {
                                method: 'POST',
                                body: postData,
                                headers: {
                                    'Content-Type': contentType,
                                },
                            }
                        };
                        if (this.onPrepareRequest)
                            this.onPrepareRequest(ev, srcData);
                        loadingHandle = undefined;
                        response = undefined;
                        if (this.onLoading)
                            loadingHandle = this.onLoading(srcData);
                        _b.label = 1;
                    case 1:
                        _b.trys.push([1, 7, 8, 9]);
                        return [4 /*yield*/, fetch(ev.url, ev.requestInit)];
                    case 2:
                        fetchRes = _b.sent();
                        if (!(fetchRes.headers["Content-Type"] === "octed/stream")) return [3 /*break*/, 4];
                        return [4 /*yield*/, fetchRes.blob()];
                    case 3:
                        response = _b.sent();
                        return [2 /*return*/, response];
                    case 4: return [4 /*yield*/, fetchRes.json()];
                    case 5:
                        response = _b.sent();
                        if (response.status == "ok") {
                            if (this.onLoaded)
                                this.onLoaded(loadingHandle, response);
                            if (newDataType)
                                response.response = new newDataType(response.response);
                            return [2 /*return*/, response.response];
                        }
                        else {
                            throw new Error(response.exception);
                        }
                        _b.label = 6;
                    case 6: return [3 /*break*/, 9];
                    case 7:
                        exc_1 = _b.sent();
                        if (this.onLoaded)
                            this.onLoaded(loadingHandle, response);
                        if (this.onError)
                            this.onError(loadingHandle, exc_1);
                        throw exc_1;
                    case 8:
                        if (this.onLoaded)
                            this.onLoaded(loadingHandle, response);
                        return [7 /*endfinally*/];
                    case 9: return [2 /*return*/];
                }
            });
        });
    };
    return BaseCL;
}());
exports.BaseCL = BaseCL;
