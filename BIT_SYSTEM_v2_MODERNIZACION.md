# BIT SYSTEM v2.0 — BIT (END-TO-END) — BIT (Basic Input Target)

> Documento de referencia para modernización del sistema de comunicación de base de datos (API).

## 1) Arquitectura del prototipo

```text
[Frontend SOMMA]
   ↓
[BIT SDK (local-first)]
   ↓
[Sync Engine]
   ↓
[BIT Server (Node.js)]
   ↓
[Event Store (in-memory / simple DB)]
   ↓
[Broadcast → clientes]
```

## 2) Backend — BIT Server (Node.js)

### `server.js`

```js
import express from "express";
import bodyParser from "body-parser";

const app = express();
app.use(bodyParser.raw({ type: "application/octet-stream" }));

// Event Store (simple)
const BIT_STORE = [];
let LAST_TS = 0;

// 🔥 SYNC ENDPOINT
app.post("/bits/sync", (req, res) => {
  const buffer = req.body;

  // Simulación decode batch
  const bits = decodeBatch(buffer);

  bits.forEach(bit => {
    BIT_STORE.push(bit);
    LAST_TS = Math.max(LAST_TS, bit.t);
  });

  res.json({
    status: "ok",
    ack: bits.map(b => b.id),
    ts: LAST_TS
  });
});

// 🔄 FETCH
app.get("/bits", (req, res) => {
  const since = Number(req.query.since || 0);

  const bits = BIT_STORE.filter(b => b.t > since);

  res.json({
    bits,
    next: LAST_TS
  });
});

app.listen(3000, () => {
  console.log("BIT Server running on 3000");
});
```

### `decodeBatch` (simplificado)

```js
function decodeBatch(buffer) {
  // Simulación: en real usar decoder binario
  return JSON.parse(buffer.toString());
}
```

## 3) SDK BIT (integrado)

### `bitEngine.js`

```js
const state = new Map();
const log = [];

export function createBIT({ type, entity, key, value, user }) {
  return {
    id: crypto.randomUUID(),
    h: type,
    e: entity,
    k: key,
    v: value,
    t: Date.now(),
    u: user,
    s: 1
  };
}

export function applyBIT(bit) {
  if (!state.has(bit.e)) state.set(bit.e, new Map());
  state.get(bit.e).set(bit.k, bit.v);
}

export function addBIT(bit) {
  log.push({ ...bit, synced: false });
}

export function getPending() {
  return log.filter(b => !b.synced);
}

export function markSynced(bits) {
  bits.forEach(b => (b.synced = true));
}

export function getState() {
  return state;
}
```

## 4) Sync Engine

### `sync.js`

```js
import { getPending, markSynced } from "./bitEngine.js";

export async function sync() {
  const pending = getPending();

  if (!pending.length) return;

  const payload = JSON.stringify(pending);

  await fetch("http://localhost:3000/bits/sync", {
    method: "POST",
    headers: {
      "Content-Type": "application/octet-stream"
    },
    body: payload
  });

  markSynced(pending);
}
```

## 5) Frontend — SOMMA Dashboard (básico)

### `app.js`

```js
import {
  createBIT,
  applyBIT,
  addBIT,
  getState
} from "./bitEngine.js";

import { sync } from "./sync.js";

// 🎯 Simulación: actualizar CTR
function updateCTR() {
  const bit = createBIT({
    type: 0x02, // UPDATE
    entity: 1,  // campaign
    key: 1,     // ctr
    value: Math.random() * 100,
    user: 1
  });

  applyBIT(bit);
  addBIT(bit);

  render();
}

// 🖥️ UI render
function render() {
  const state = getState();

  const campaign = state.get(1);
  const ctr = campaign?.get(1) || 0;

  document.getElementById("ctr").innerText =
    `CTR: ${ctr.toFixed(2)}%`;
}

// 🔄 Auto sync
setInterval(sync, 1000);

// UI
document.getElementById("btn").onclick = updateCTR;
```

### `index.html`

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>SOMMA Prototype</h1>
    <div id="ctr">CTR: 0%</div>
    <button id="btn">Actualizar CTR</button>

    <script type="module" src="app.js"></script>
  </body>
</html>
```

## 6) Flujo completo

1. Usuario hace click.
2. Se crea BIT (UPDATE CTR).
3. Se aplica localmente (instantáneo).
4. UI se actualiza.
5. BIT se guarda en log.
6. Sync envía batch.
7. Server guarda evento.
8. Otros clientes podrían recibirlo.

## 7) Resultado funcional

Con esto ya tienes:

- ✅ Sistema local-first
- ✅ Event sourcing básico
- ✅ Sync real
- ✅ Backend funcional
- ✅ UI reactiva

## 8) Extensión inmediata (siguiente nivel)

1. **Reemplazar JSON por binario**
   - Protobuf / custom encoder.

2. **Agregar WebSocket**

```js
const ws = new WebSocket("ws://localhost:3000");

ws.onmessage = (msg) => {
  const bit = JSON.parse(msg.data);
  applyBIT(bit);
  render();
};
```

3. **Agregar firma**

```js
bit.sig = sign(bit);
```

4. **Diccionario real**
   - `entity = 1 → campaign`
   - `key = 1 → ctr`

5. **Snapshot**
   - Guardar estado cada 100 BITs.
