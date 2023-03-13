let currencyListApp = angular.module('currencyListApp', ['listModule', 'apiModule', 'swangular']);

currencyListApp.controller('currencyListAppCtrl', ['$scope', 'api', 'listManager', 'swangular', function($scope, api, listManager, swangular){
    listManager.initFilterFields(['fullName', 'shortName']);

    $scope.listManager = listManager;
    $scope.results = [];
    $scope.pages = [];

    let refreshList = function(){
        listManager.filtersApply();

        api.getCurrencies(listManager.options, function (result) {
            listManager.processResult(result);

            $scope.results = result.result;
            $scope.pages = listManager.generatePages(result);
        }, function(){
            // error handler
        });
    };

    $scope.setSortBy = function(field){
        listManager.setSortBy(field, function(){
            refreshList();
        });
    };

    $scope.changePage = function(page){
        listManager.changePage(page, function(){
            refreshList()
        });
    };
    $scope.changePageSize = function(pageSize){
        listManager.changePageSize(pageSize, function(){
            refreshList()
        });
    };
    $scope.search = function(){
        refreshList();
    };

    refreshList();

//// ###################################################################################################################

    $scope.currencyTypes = [];
    let loadCurrencyTypes = function(){
        api.getCurrencyTypes(function (result) {
            $scope.currencyTypes = result.types;

        }, function(){
            // error handler
        });
    };
    loadCurrencyTypes();

//// ###################################################################################################################

    let validateCurrency = function(callback){
        if(!$scope.isTypeERC20()){
            $scope.currencyFormData.smartContractAddress = null;
        }

        if(!$scope.currencyFormData.fullName){
            swangular.swal("Something is wrong", "Currency full name is required. Please make sure that the field is filled.", "warning");
            return false;
        }
        if(!$scope.currencyFormData.shortName){
            swangular.swal("Something is wrong", "Currency short name is required. Please make sure that the field is filled.", "warning");
            return false;
        }
        if(!$scope.currencyFormData.type){
            swangular.swal("Something is wrong", "Currency type is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        if(!$scope.currencyFormData.smartContractAddress && $scope.isTypeERC20()){
            swangular.swal("Something is wrong", "Smart contract address is required for ERC20 type. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            fullName: $scope.currencyFormData.fullName,
            shortName: $scope.currencyFormData.shortName,
            type: $scope.currencyFormData.type,
            smartContractAddress: $scope.currencyFormData.smartContractAddress,
        });
    };

    let resetCurrencyForm = function(){
        $scope.currencyFormData = {
            fullName: null,
            shortName: null,
            type: null,
            smartContractAddress: null,
        };
    };
    resetCurrencyForm();

    $scope.isTypeERC20 = function(){
        if($scope.currencyFormData.type === 'erc20'){
            return true;
        }

        return false;
    };

    $scope.saveCurrency = function () {
        validateCurrency(function(data){
            api.postCurrency(data, function (result) {
                swangular.swal("Success", "New currency saved. Feel free to add another one.", "success");

                resetCurrencyForm();

                refreshList();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };

//// ###################################################################################################################

    $scope.disableCurrency = function(currencyId){
        api.putCurrencyDisable(currencyId, function (result) {
            angular.forEach($scope.results, function(currency){
                if(currency.id === currencyId){
                    let index = $scope.results.indexOf(currency);
                    $scope.results[index].enabled = false;
                }
            });

            swangular.swal("Success", "Currency disabled.", "success");
        }, function(){
            // error handler
        });
    };

    $scope.enableCurrency = function(currencyId){
        api.putCurrencyEnable(currencyId, function (result) {
            angular.forEach($scope.results, function(currency){
                if(currency.id === currencyId){
                    let index = $scope.results.indexOf(currency);
                    $scope.results[index].enabled = true;
                }
            });

            swangular.swal("Success", "Currency enabled.", "success");
        }, function(){
            // error handler
        });
    };

    $scope.changeFeeCurrency = function (currency) {
        api.putCurrencyFee({'fee': currency.fee}, currency.id, function (result) {
            swangular.swal("Success", "Currency withdrawal fee has been changed.", "success");
        }, function(){
            swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
        });
    };

//// ###################################################################################################################

    let validateCurrencyPair = function(callback){
        if(!$scope.currencyPairFormData.baseCurrency){
            swangular.swal("Something is wrong", "Base currency is required. Please make sure that the field is filled.", "warning");
            return false;
        }
        if(!$scope.currencyPairFormData.quotedCurrency){
            swangular.swal("Something is wrong", "Quoted currency is required. Please make sure that the field is filled.", "warning");
            return false;
        }

        callback({
            baseCurrencyId: $scope.currencyPairFormData.baseCurrency,
            quotedCurrencyId: $scope.currencyPairFormData.quotedCurrency,
        });
    };

    let resetCurrencyPairForm = function(){
        $scope.currencyPairFormData = {
            baseCurrency: null,
            quotedCurrency: null,
        };
    };
    resetCurrencyPairForm();

    $scope.saveCurrencyPair = function () {
        validateCurrencyPair(function(data){
            api.postCurrencyPair(data, function (result) {
                swangular.swal("Success", "New currency pair saved. Feel free to add another one.", "success");

                resetCurrencyPairForm();

                refreshList();
            }, function(){
                swangular.swal("Something is wrong", "Please make sure that all fields are filled.", "warning");
            });
        });
    };
}]);

angular.bootstrap(document.getElementById("currencyListAppHandler"),["currencyListApp"]);
