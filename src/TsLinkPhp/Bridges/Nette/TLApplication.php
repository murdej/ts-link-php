<?php declare(strict_types = 1);

namespace Murdej\TsLinkPhp\Bridges\Nette;

use Murdej\TsLinkPhp\MiddlewareInterface;
use Murdej\TsLinkPhp\TsLink;
use Murdej\TsLinkPhp\TsCodeGenerator;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Tracy\Debugger;

class TLApplication
{

    protected array $cNameToName = [];

    protected array $cls = [];

    /**
     * @var string|null
     */
    public string|null $codeGenFile = null;

    public string $codeGenFormat = 'ts';

    public string $codeGenCNRemovePrefix = '';

    public string $codeGenCNRemoveSufix = 'TL';

    public bool $codeGenEnabled = true;

    public array $middlewares = [];

    public string $debugger = self::Debugger_Nette;

    public function addMiddleware(MiddlewareInterface $middleware) : void {
        $this->middlewares[] = $middleware;
    }

    public const string Debugger_Nette = 'Nette';
    public const string Debugger_Json = 'Json';
    public const string Debugger_Text = 'Text';
    public const string Debugger_Hide = 'Hide';

    /** @throws */
    public function run(): void
    {
        if ($this->debugger === self::Debugger_Nette) set_error_handler([Debugger::class, 'errorHandler']);
        $origin = $this->httpRequest->getHeader('origin');
        if ($this->cors === true || (is_array($this->cors) && in_array($origin, $this->cors))) {
            $requestMethod = $this->httpRequest->getMethod();
            $allowedMethods = 'GET, POST, PUT, DELETE, OPTIONS';
            $allowedHeaders = 'Content-Type, Authorization, X-Requested-With';
            $maxAge = 1;

            $this->httpResponse->setHeader('Access-Control-Allow-Origin', $origin);
            $this->httpResponse->setHeader('Access-Control-Allow-Methods', $allowedMethods);
            $this->httpResponse->setHeader('Access-Control-Allow-Headers', $allowedHeaders);
            $this->httpResponse->setHeader('Access-Control-Allow-Credentials', 'true');
            $this->httpResponse->setHeader('Access-Control-Max-Age', (string)$maxAge);

            if ($requestMethod === 'OPTIONS' && $origin) {
                $this->httpResponse->setCode(204);
                return;
            }

        }
        try {
            $name = substr($this->httpRequest->getUrl()->path, strlen($this->urlPrefix));
            if ($name === '@code-gen' || $name === '@code-dump') {
                if (!$this->codeGenEnabled || (!$this->codeGenFile && $name === '@code-gen')) throw new BadRequestException('Not allowed', 405);
                switch ($name) {
                    case '@code-gen':
                        file_put_contents($this->codeGenFile, $this->generateClientCode());
                        break;
                    case '@code-dump':
                        $this->httpResponse->setContentType('text/plain', 'utf-8');
                        $hres = new TextResponse($this->generateClientCode());
                        $hres->send($this->httpRequest, $this->httpResponse);
                        break;
                }
            } else {
                if (!isset($this->cls[$name])) throw new BadRequestException("No TsLink with name '$name'", 404);
                $cl = $this->cls[$name];
                if (method_exists($cl, 'startup')) $cl->startup();

                $tsl = new TsLink($cl);
                $tsl->middlewares = $this->middlewares;
                if (str_starts_with($this->httpRequest->getHeader('Content-Type'), 'multipart/form-data')) {
                    $request = $this->httpRequest->getPost('request');
                    $files = $this->httpRequest->getFiles();
                } else {
                    $request = $this->httpRequest->getRawBody();
                    $files = [];
                }
                $res = $tsl->processRequest($request, $files);

                $filePath = $res->getFilePath();
                if ($filePath) {
                    (new FileResponse($filePath, null, $res->getContentType(), false))->send($this->httpRequest, $this->httpResponse);
                } else {
                    $this->httpResponse->setContentType($res->getContentType());
                    (new TextResponse($res->getTextContent()))->send($this->httpRequest, $this->httpResponse);
                }
            }
        } catch (\Throwable $e) {
            if ($this->debugger === self::Debugger_Nette) Debugger::exceptionHandler($e);
            else {
                $this->httpResponse->setCode(IResponse::S500_InternalServerError);
                $errorResponse = '';
                switch($this->debugger) {
                    case self::Debugger_Json:
                        $errorResponse = json_encode(
                            [
                                'code' => $e->getCode(),
                                'message' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'trace' => $e->getTrace(),
                            ],
                            JSON_PARTIAL_OUTPUT_ON_ERROR
                        );
                        break;
                    case self::Debugger_Text:
                        $errorResponse = (string)$e;
                        break;
                }
                $this->httpResponse->setContentType($this->debugger === self::Debugger_Json ? 'application/json' : 'text/plain', 'utf-8');
                (new TextResponse($errorResponse))->send($this->httpRequest, $this->httpResponse);
            }
        }
    }

    public function __construct(
        public string $urlPrefix,
        protected IRequest $httpRequest,
        protected IResponse $httpResponse,
        protected array|bool $cors = false
    )
    {
    }

    public function generateClientCode() : string
    {
        $tsg = new TsCodeGenerator();
        foreach($this->getRegisterClasses() as $cn) {
            $tsg->add($cn, $this->getLinkForClass($cn));
        }
        $tsg->format = $this->codeGenFormat;
        
        return $tsg->generateCode();
    }

    public function add(/*BaseCL */$cl, string |null $name = null): void
    {
        $cName = get_class($cl);
        if (!$name) {
            $name = $cName;
            if (Strings::startsWith($name, $this->codeGenCNRemovePrefix)) $name = substr($name, strlen($this->codeGenCNRemovePrefix));
            if (Strings::endsWith($name, $this->codeGenCNRemoveSufix)) $name = substr($name, 0, -strlen($this->codeGenCNRemoveSufix));
            $name = Strings::webalize($name);
            $newName = $name;
            $i = 0;
            while (in_array($newName, $this->cNameToName)) {
                $i++;
                $newName = $name . '_' . $i;
            }

            $name = $newName;
        }

        $this->cls[$name] = $cl;
        $this->cNameToName[$cName] = $name;
    }

    public function getLinkForClass(string $cName): string
    {
        return $this->urlPrefix . '' . $this->cNameToName[$cName];
    }

    public function getLinkForName(string $name): string
    {
        return $this->urlPrefix . '/' . $name;
    }

    public function getRegisterClasses(): array
    {
        return array_keys($this->cNameToName);
    }

}
