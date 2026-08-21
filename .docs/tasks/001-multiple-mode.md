# Batch mode

Cíl je umožnit obsloužit v jednom requestu více samostatných požadavků.

Fungovat to bude takto:

 - FE
   - Při zapnutém batch mode se nebudou volat http požadavky ale uloží se do fronty a vracet promise, po zavolá .send() se pošle na server celá fronta.
   - Nová metoda .useBatch(callback: ((tsLink|self)=>void)|null)
     - pokud je zadán callback, kde programtor vola jednotlivé metody, provede se a zavolá .send() a vrátí návratová hodnota callbacku
     - pokud ne .send() volá programátor
   - nebo .useBatchAuto(config: BatchConfig|null) - vrátí klon instance se zapnutým příznakem batch mode
     - programátor volá jednotlivé metody
     - .send() se zavolá automaticky v jednom z těchto případů:
       - od posledního volání callMethod uplynulo právě nebo více něž config.sleepTimeout nebo od prvního volání uplynulo právě nebo více něž  config.maxTimeout, nebo je ve frontě právě nebo více něž config.maxRequests
    
# Konfigurace:
```javascript
BatchConfig = {
    sleepTimeout: 100,
    maxTimeout: 1000,
    maxRequests: 10
}
```

# Příklady použití:

```typescript
const tl = new AppTL().useBatchAuto({sleepTimeout: 100, maxTimeout: 1000, maxRequests: 10});
tl.getUser().then(data => showUser(data));
tl.getCart().then(data => showCart(data));
tl.getNotifies().then(data => showNotifies(data));
```
nebo

```typescript
const [
     user,
     cart,
     notifies,
] = await Promise.all(
    new AppTL().useBatch(tl => [
        tl.getUser(),
        tl.getCart(),
        tl.getNotifies(),
    ])
);
```
nebo

```typescript
const tl = new AppTL().useBatch();
tl.getUser().then(data => showUser(data));
tl.getCart().then(data => showCart(data));
tl.getNotifies().then(data => showNotifies(data));
tl.send();
```

# Komunikace

Při posílání v běžném režimu:

```
request:
{
    "name": "getUser",
    "context": {},
    "pars": [
        16
    ],
    "uploadArgs": []
}

response:
{
    "status": "ok",
    "response": { "name": "John" }
}
```

Při posílání v batch režimu:

```
request:
{
    "batch": [
        {
            "id": 1,
            "name": "getUser",
            "context": {},
            "pars": [
                16
            ],
            "uploadArgs": []
        },
        {
            "id": 2,
            "name": "getCart",
            "context": {},
            "pars": [
                16
            ],
            "uploadArgs": []
        }
    ]
}

response:
{
    "batch": [
        {
            "id": 1,        
            "status": "ok",
            "response": { "name": "John" },
        }
        {
            "id": 2,        
            "status": "ok",
            "response": [],
        }
    ]
}
```

# Backend

Pokud backend uvidí v požadavku klíč `batch` místo name, tak se zpracuje jednotlivé položky jako jeden request. Pokud `$service` implementuje `IBatchCall` zavolá metodu batchCall kde bude možné provést například vyčištění cache nebo znovuvytvoření service. 

Všechny výsledky se potom vrací najednou.