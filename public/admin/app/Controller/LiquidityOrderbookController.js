function LiquidityOrderbookController($scope, api, routingModule, swangular, $websocket, bridge){
    $scope.routing = routingModule;

    $scope.bids = [];
    $scope.offers = [];

    $scope.buyAmount = 0;
    $scope.buyPrice = 0;

    $scope.sellAmount = 0;
    $scope.sellPrice = 0;

    $scope.buy = function(){
        let data = {
            'currencyPairId': 7,
            'type': 1,
            'amount': $scope.buyAmount,
            'limitPrice': $scope.buyPrice
        };

        api.postOrder(data, function (result) {
            swangular.swal("Success", "Deposit request added. It is waiting for additional approval.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
        });
    };

    $scope.sell = function(){
        let data = {
            'currencyPairId': 7,
            'type': 2,
            'amount': $scope.sellAmount,
            'limitPrice': $scope.sellPrice
        };

        api.postOrder(data, function (result) {
            swangular.swal("Success", "Deposit request added. It is waiting for additional approval.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
        });
    };

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
        'module': 'externalOrderbook',
        'currencyPairShortName': 'BTC-USDT'
    }));
};
