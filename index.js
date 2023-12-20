'use strict';

const line = require('@line/bot-sdk');
const express = require('express');

// create LINE SDK config from env variables
const config = {
  channelAccessToken: '0TevYWlBWrF/rrggthdzBo6VbsPP4tav1uitAysfptCGWQ1qUZenKjfIEiDIGIicYR3m2OwqD4SoP+ff1p3CxRwdyDcGmt5+WYmx9sj41aUHNTO1qsq34Bg0ixdKcY91xUd4YdluiZikitiKvAE/BgdB04t89/1O/w1cDnyilFU=',
  channelSecret: '9a40cc121dff3df14ade91f9cd397926',
};

// create LINE SDK client
const client = new line.messagingApi.MessagingApiClient({
  channelAccessToken: '0TevYWlBWrF/rrggthdzBo6VbsPP4tav1uitAysfptCGWQ1qUZenKjfIEiDIGIicYR3m2OwqD4SoP+ff1p3CxRwdyDcGmt5+WYmx9sj41aUHNTO1qsq34Bg0ixdKcY91xUd4YdluiZikitiKvAE/BgdB04t89/1O/w1cDnyilFU='
});

// create Express app
// about Express itself: https://expressjs.com/
const app = express();

// register a webhook handler with middleware
// about the middleware, please refer to doc
app.post('/callback', line.middleware(config), (req, res) => {
  Promise
    .all(req.body.events.map(handleEvent))
    .then((result) => res.json(result))
    .catch((err) => {
      console.error(err);
      res.status(500).end();
    });
});

// event handler
function handleEvent(event) {
  if (event.type !== 'message' || event.message.type !== 'text') {
    // ignore non-text-message event
    return Promise.resolve(null);
  }

  // create an echoing text message
  const echo = { type: 'text', text: event.message.text };

  // use reply API
  return client.replyMessage({
    replyToken: event.replyToken,
    messages: [echo],
  });
}

// listen on port
const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`listening on ${port}`);
});