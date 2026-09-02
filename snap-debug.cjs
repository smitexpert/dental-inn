// Minimal CDP screenshot — no extra deps. Connect to chrome via WebSocket.
const http = require('http');
const WebSocket = require('ws');

function get(url) {
  return new Promise((resolve, reject) => {
    http.get(url, (res) => {
      let d = ''; res.on('data', c => d += c); res.on('end', () => resolve(d));
    }).on('error', reject);
  });
}

(async () => {
  const tabs = JSON.parse(await get('http://172.18.0.12:9222/json'));
  const t = tabs.find(x => x.type === 'page') || tabs[0];
  const ws = new WebSocket(t.webSocketDebuggerUrl);
  let id = 0;
  const pending = new Map();
  ws.on('message', (m) => {
    const msg = JSON.parse(m);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); }
  });
  await new Promise(r => ws.on('open', r));
  const send = (method, params) => new Promise((resolve) => {
    const i = ++id; pending.set(i, resolve);
    ws.send(JSON.stringify({ id: i, method, params: params || {} }));
  });
  await send('Network.enable');
  await send('Network.setCookie', {
    name: 'laravel-session', value: 'pil4yupuaNSZ2emmdAmPpZFaOAD0kfe0vfdmKNCy',
    domain: '172.18.0.12', path: '/', httpOnly: true,
  });
  await send('Page.enable');
  await send('Page.navigate', { url: 'http://172.18.0.12/doctors' });
  await new Promise(r => setTimeout(r, 1500));
  const shot = await send('Page.captureScreenshot', { format: 'png', clip: { x: 0, y: 0, width: 1440, height: 700, scale: 1 } });
  require('fs').writeFileSync('/tmp/snap/doctors.png', Buffer.from(shot.result.data, 'base64'));
  console.log('written');
  ws.close();
})().catch(e => { console.error(e); process.exit(1); });
