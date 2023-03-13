function OrderbookController($scope, api, routingModule, swangular, $websocket, bridge){
    $scope.routing = routingModule;

    $scope.bids = [];
    $scope.offers = [];

    let ws = $websocket('ws://localhost:8080');

    ws.onMessage(function(message) {
        if(message.data !== 'ok'){
            let data = JSON.parse(message.data);
            $scope.bids = data.orderbook.bidOrders;
            $scope.offers = data.orderbook.offerOrders;
        }
    });

    ws.send(JSON.stringify({
        'action': 'subscribe',
        'module': 'orderbook',
        'currencyPairShortName': 'BTC-USDT'
    }));
};
