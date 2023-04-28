let app = require('express')();
let http = require('http').Server(app);
const io = require("socket.io")(http);

http.listen(4000, () => {
  console.log('Listening on port *: 4000');
});

io.on("connection", socket => {

  socket.on('sendData', function (data) { 
    io.emit('sendData', data);
    console.log(data); 
  }); // listen to the event

});