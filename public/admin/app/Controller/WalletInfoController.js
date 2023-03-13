function WalletInfoController($scope, api){
    $scope.walletId = null;
    $scope.wallet = [];

    $scope.analysis = null;
    $scope.analysisAllowed = true;

    let loadWallet = function(){
        api.getWallet($scope.walletId, function (result) {
            $scope.wallet = result.wallet;

            loadAnalysis();
        }, function(){
            // error handler
        });
    };

    let loadAnalysis = function(){
        $scope.analysis = null;
        $scope.analysisAllowed = true;

        api.getWalletAnalysis($scope.walletId, function (result) {
            if(result.walletAnalysis){
                $scope.analysis = result.walletAnalysis;
                $scope.analysisAllowed = true;
            }else{
                $scope.analysisAllowed = false
            }
        }, function(){
            $scope.analysisAllowed = false;
        });
    };

    $scope.loadAnalysis = function(){
        loadAnalysis();
    };

    $scope.$on('refreshWalletInfo', function() {
        loadWallet();
    });

    $scope.$on('loadedWallet', function(event, args) {
        $scope.walletId = args.walletId;
        loadWallet();
    });
};