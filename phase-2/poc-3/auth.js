const http = require('http');
const url = require('url');
const { Client } = require('./Client');
const { AuthServer } = require('./AuthServer');
const { makeid } = require('./util');

const OUR_PORT = 3002;
const client = new Client({
  authServerPort: 3003,
  clientId: 'AS',
  clientSecret: 'ooD4butoomaiGhoo3EiH'
});
const clients = {
  'surf-research-cloud': {
    clientSecret: 'oodeiB2deikeer4doopa',
    redirectUri: 'http://localhost:3001/callback',
    label: 'SRC VM 1234'
  }
};
const server = new AuthServer({
  resourceHelperPort: 3003,
  clients
});

function handleOverview(req, res, serverData) {
  res.writeHead(200, {'Content-Type': 'text/html'});
  res.write(`
    <body style="background-color:#faf9e3">
    <h2>Auth server (AS)</h2>
    <p>Logged in as <tt>alice@surf.nl</tt></p>
    <p>Overview:</p>
    <ul>`);
  Object.keys(serverData.grants).forEach(grant => {
      res.write(`<li>${grant}</li>`);
  });
  res.end(`</ul></body>`);
}

http.createServer(async (req, res) => {  
  // console.log(req.url.toString());
  if (req.url.startsWith('/callback')) {
    const upstreamScope = client.getScopeFromCallback(req.url);
    const upstreamState = client.getStateFromCallback(req.url);
    // console.log('callback', upstreamCode, upstreamScope, upstreamState);
    const { clientState, clientId } = server.getTicket(upstreamState);
    const clientLabel = clients[clientId].label;
    const clientRedirectUri = clients[clientId].redirectUri;
    const upstreamInfo = server.localResourceRegistryLookup(upstreamScope);
    const downstreamCode = makeid('as-code-', 8);
    const downstreamScopeId = 'research-drive:' + upstreamScope;
    server.storeGrant(downstreamCode, downstreamScopeId);
    server.storeDownstreamScopeInfo(downstreamScopeId, {
      type: "grant",
      humanReadable: {
        "en-US": `[from SURF Research Drive] ${upstreamInfo.description}`
      },
      machineReadableInternal: upstreamInfo.machineReadableInternal,
      protocols: {
        "webdav": {
            "url": "https://dav.rd123.surf.nl:4523/pietjepuk/files/photos/2023/January",
            "protocol-version": "8.6n"
        }
      }
    });
    const downstreamCallbackUrl = server.createCallbackUrl({
      clientId,
      code: downstreamCode,
      scope: downstreamScopeId,
      state: clientState,
    })
    res.writeHead(200, {'Content-Type': 'text/html'});
    res.end(`
      <body style="background-color:#faf9e3">
      <h2>Auth server (AS)</h2>

      <h2>Are you sure?</h2>
      Are you sure you want to share "${upstreamInfo.description}" with client "${clientLabel}"?<br><a href="${downstreamCallbackUrl}">yes</a> / <a href="no.html">no</a>
      <h2>Data:</h2>
      <pre>${JSON.stringify(server.getData(), null, 2)}</pre>
    `);
  } else if (req.url?.startsWith('/authorize')) {
    const url_parts = url.parse(req.url, true);
    const query = url_parts.query;
    // console.log('new transaction', query);
    if (query.scope == 'webdav-folder') {
      // console.log(`need to pick ${query.scope}!`);
      if (query.state && query.client_id) {
        const clientState = query.state;
        const upstreamTicket = makeid('as-ticket-', 8);
        server.storeTicket(upstreamTicket, { clientState, clientId: query.client_id });
        const upstreamUrl = client.makeAuthorizeUrl(query.scope, upstreamTicket, 'scope');
        res.end(`
          <body style="background-color:#faf9e3">
          <h2>Auth server (AS)</h2>
          <p>Logged in as <tt>alice@surf.nl</tt></p>
          <p>Here are some SURF services you may want to share resources from, connected to your account:</p>
          <ul>
            <li><a href="${upstreamUrl}">Research Drive</a></li>
            <li><a href="">iRods</a></li>
            <li><a href="">Microsoft Outlook Calendar</a></li>
          </ul>
          <h2>Data:</h2>
          <pre>${JSON.stringify(server.getData(), null, 2)}</pre>
        `);
      }
    }
  } else  if (req.url?.startsWith('/token')) {
    server.handleToken(req, res);
  } else  if (req.url?.startsWith('/scope')) {
    server.handleScopeInfo(req, res);
  } else  if (req.url?.startsWith('/resource-registry')) {
    server.handleResourceRegistry(req, res);
  } else {
    handleOverview(req, res, server.getData());
  }
}).listen(OUR_PORT);
console.log(`AS is running on port ${OUR_PORT}`);
