let apiModule = angular.module('apiModule', ['ngCookies', 'apiRoutesModule']);

apiModule.factory('cookieStorage', ServiceCookieStorage);
apiModule.factory('api', function($http, $httpParamSerializer, $window, apiRoutes, cookieStorage) {
    //$http.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    //$http.defaults.headers.post["Content-Type"] = "application/json";

    let authToken = null;

    let buildAuthHeader = function(token){
        return {
            'Authorization': 'Bearer ' + token
        };
    };

    let saveToken = function(token){
        cookieStorage.authToken.save(token);
    };

    let resolveUrl = function(url){
        return url;
        return url.replace('http://', 'https://')
    };

    let waiting = false;

    let getMe = function(token, callback, callbackError){
        waiting = true;

        $http({
            method: 'GET',
            url: resolveUrl(apiRoutes.user.me()),
            headers: buildAuthHeader(token)
        }).then(function successCallback(response) {
            callback(response.data);
        }, function errorCallback(response) {
            callbackError(false);       // TODO error message handler
        });
    };

    let obtainToken = function(username, password, reCaptcha, callback, callbackError){
        $http({
            method: 'POST',
            url: resolveUrl(apiRoutes.auth.login()),
            data: {
                username: username,
                password: password,
                'g-recaptcha-response': reCaptcha
            },
        }).then(function successCallback(response) {
            if(!(typeof response.data.token === typeof undefined)){
                authToken = response.data.token;
                saveToken(authToken);

                callback(authToken);
            }
        }, function errorCallback(response) {
            callbackError(response);
        });
    };

    let resolveAuth = function(callback){
        if(waiting){
            setTimeout(function () {
                if(authToken){
                    callback(authToken);
                }else{
                    let cookieToken = cookieStorage.authToken.load();
                    if(cookieToken){
                        getMe(cookieToken, function (result) {
                            if(result.user){
                                // token is valid
                                authToken = cookieToken;
                                waiting = false;
                                callback(authToken);
                            }
                        }, function(){
                            // token expired // redirect to login page

                            authToken = null;
                            waiting = false;
                            $window.location.href = Routing.generate('admin_login', { }, true);
                        });
                    }else{
                        // redirect to login page

                        authToken = null;
                        waiting = false;
                        $window.location.href = Routing.generate('admin_login', { }, true);
                    }
                }
            }, 2000);
        }else{
            if(authToken){
                callback(authToken);
            }else{
                let cookieToken = cookieStorage.authToken.load();
                if(cookieToken){
                    getMe(cookieToken, function (result) {
                        if(result.user){
                            // token is valid
                            authToken = cookieToken;
                            waiting = false;
                            callback(authToken);
                        }
                    }, function(){
                        // token expired // redirect to login page

                        authToken = null;
                        waiting = false;
                        $window.location.href = Routing.generate('admin_login', { }, true);
                    });
                }else{
                    // redirect to login page

                    authToken = null;
                    waiting = false;
                    $window.location.href = Routing.generate('admin_login', { }, true);
                }
            }
        }
    };

    return {
        resolveUrl: resolveUrl,
        apiRoutes: apiRoutes,
        getTokenHeaders: function(){
            return buildAuthHeader(cookieStorage.authToken.load());
        },
        initializeApi: function(){
            resolveAuth(function(token){ });
        },
        login: obtainToken,
        getCountries: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.country.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWallets: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.wallet.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWallet: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.wallet.get(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putWalletInternalTransfer: function(fromWalletId, toWalletId, amount, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.wallet.internalTransfer(fromWalletId, toWalletId, amount)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putWalletReleaseBlocked: function(walletId, amount, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.wallet.releaseBlocked(walletId, amount)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWalletPendingOrders: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.wallet.pendingOrders(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWalletAnalysis: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.wallet.analysis(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWalletBanks: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.wallet.banks(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postWalletBank: function(walletId, formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.wallet.postBank(walletId)),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postUserWalletAddress: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.wallet.postAddress(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Internal transfers
//
        getInternalTransfers: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.internalTransfer.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getInternalTransfer: function(internalTransferId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.internalTransfer.get(internalTransferId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putInternalTransferDecline: function(internalTransferId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.internalTransfer.decline(internalTransferId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putInternalTransferApprove: function(internalTransferId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.internalTransfer.approve(internalTransferId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putInternalTransferReject: function(internalTransferId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.internalTransfer.reject(internalTransferId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putInternalTransferRevert: function(internalTransferId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.internalTransfer.revert(internalTransferId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
//##################
//## Withdrawals
//

        getWithdrawals: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.withdrawal.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWithdrawal: function(withdrawalId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.withdrawal.get(withdrawalId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putWithdrawalSendForExternalApproval: function(withdrawalId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.withdrawal.externalApproval(withdrawalId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putWithdrawalDecline: function(withdrawalId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.withdrawal.decline(withdrawalId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putWithdrawalApprove: function(withdrawalId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.withdrawal.approve(withdrawalId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putWithdrawalReject: function(withdrawalId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.withdrawal.reject(withdrawalId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Voter roles
//
        getVoterRoles: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.voterRole.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postVoterRole: function(formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.voterRole.post()),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
//##################
//## System tags
//
        getSystemTags: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.systemTag.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putSystemTagToggle: function(systemTagId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.systemTag.toggle(systemTagId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Deposits
//
        getDeposits: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.deposit.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getDeposit: function(depositId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.deposit.get(depositId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getDepositBlockchainTx: function(depositId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.deposit.blockchainTx(depositId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postDeposit: function(formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.deposit.post()),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putDepositApprove: function(depositId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.deposit.approve(depositId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putDepositDecline: function(depositId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.deposit.decline(depositId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putDepositRevert: function(depositId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.deposit.revert(depositId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Currencies
//
        getCurrencyTypes: function(callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.currency.types()),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getCurrencies: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.currency.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postCurrency: function(formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.currency.post()),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putCurrencyDisable: function(currencyId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.currency.disable(currencyId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putCurrencyEnable: function(currencyId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.currency.enable(currencyId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putCurrencyFee: function(formData, currencyId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.currency.fee(currencyId)),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Currency pairs
//
        getCurrencyPairs: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.currencyPair.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postCurrencyPair: function(formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.currencyPair.post()),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putCurrencyPairDisable: function(currencyPairId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.currencyPair.disable(currencyPairId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putCurrencyPairEnable: function(currencyPairId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.currencyPair.enable(currencyPairId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Trades
//
        getTrades: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.trade.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## CheckoutOrders
//
        getCheckoutOrders: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.checkoutOrder.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Workspaces
//
        getWorkspaces: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.workspace.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Employees
//
        getEmployees: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.employee.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## POSOrders
//
        getPOSOrders: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.POSOrder.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Orders
//
        getOrders: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.order.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postOrder: function(formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.order.post()),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
//##################
//## Users
//
        getUsers: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUser: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.get(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserVoterRoles: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.voterRoles(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserApiKeys: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.apiKeys(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserLoginHistory: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.loginHistory(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserPendingOrders: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.pendingOrders(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserDeposits: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.deposits(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserWithdrawals: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.withdrawals(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserBanks: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.banks(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getUserPepInfo: function(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.pepInfo(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserApiKeyDeactivate(userId, key, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.apiKeyDeactivate(userId, key)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserDeny(userId, voterRoleId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.deny(userId, voterRoleId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserGrant(userId, voterRoleId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.grant(userId, voterRoleId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserTagAssign(userId, tag, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.assignTag(userId, tag)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserTagUnassign(userId, tag, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.unassignTag(userId, tag)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserUpdateData: function(userId, formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.updateData(userId)),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putVerificationStatus(userId, status, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.setVerificationStatus(userId, status)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        patchRemoveUser(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PATCH',
                    url: resolveUrl(apiRoutes.user.removeUser(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserToggleEmailConfirmed(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.toggleEmailConfirmed(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserToggleTradingEnabled(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.toggleTradingEnabled(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserResendConfirmation(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.resendConfirmation(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserResendEmailNotification(userId, notificationType, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.resendEmailNotification(userId, notificationType)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        putUserGAuthDisable(userId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'PUT',
                    url: resolveUrl(apiRoutes.user.disableGAuth(userId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        postUserBank: function(userId, formData, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'POST',
                    url: resolveUrl(apiRoutes.user.postBank(userId)),
                    data: angular.toJson(formData),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getTodayStatistics: function(callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.user.todayStatistics()),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## FinancialReports
//
        getLiquidityReports: function(callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.financialReports.liquidityReports()) + '?from=2020-01-01',
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

        getLiquidityTransactions: function(currencyPairId, from, to, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.liquidity.liquidityTransactions(currencyPairId)) + '?from=' + from + '&to=' + to,
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

        getBalances: function(walletType, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.financialReports.balances()) + '?type=' + walletType,
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

        getIncomingFees: function(from, to, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.financialReports.incomingFees()) + '?from=' + from + '&to=' + to,
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

        getLiquidityBalances: function(callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.financialReports.liquidityBalances()),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Transfers
//
        getTransfersByWallet: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.transfer.list.byWallet(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
//##################
//## Wallet balances
//
        getWalletBalancesByWallet: function(walletId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.walletBalance.list.byWallet(walletId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
//##################
//## Wallet transfers
//
        getWalletTransferByTrade: function(tradeId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.walletTransfer.one.byTrade(tradeId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWalletTransfersByOrder: function(orderId, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.walletTransfer.list.byOrder(orderId)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
        getWalletTransfersNotProcessed: function(callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.walletTransfer.list.notProcessed()),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## Trading transaction
//
        getTradingTransactionsNotProcessed: function(callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.tradingTransaction.list.notProcessed()),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },

//##################
//## GiifReports
//
        getGiifReports: function(params, callback, callbackError){
            resolveAuth(function(token){
                $http({
                    method: 'GET',
                    url: resolveUrl(apiRoutes.giifReports.list() + '?' + $httpParamSerializer(params)),
                    headers: buildAuthHeader(token)
                }).then(function successCallback(response) {
                    callback(response.data);
                }, function errorCallback(response) {
                    callbackError(false);       // TODO error message handler
                });
            });
        },
    }
});
