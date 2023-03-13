let apiRoutesModule = angular.module('apiRoutesModule', []);

apiRoutesModule.factory('apiRoutes', function() {
    return {
        auth: {
            login: function(){ return Routing.generate('login_check', {}, true); }
        },
        country: {
            list: function(){ return Routing.generate('app_publicapi_getcountries', {}, true); },
        },
        wallet: {
            get: function(walletId){ return Routing.generate('app_apiadmin_wallet_getwallet', { 'walletId': walletId }, true); },
            list: function(){ return Routing.generate('app_apiadmin_wallet_getwallets', {}, true); },
            internalTransfer: function(fromWalletId, toWalletId, amount){
                return Routing.generate('app_apiadmin_wallet_putwalletinternaltransfer', {
                    'walletId': fromWalletId, 'toWalletId': toWalletId, 'amount': amount
                }, true);
            },
            releaseBlocked: function(walletId, amount){
                return Routing.generate('app_apiadmin_wallet_putwalletreleaseblocked', {
                    'walletId': walletId, 'amount': amount
                }, true);
            },
            pendingOrders: function(walletId){ return Routing.generate('app_apiadmin_wallet_getwalletpendingorders', { 'walletId': walletId }, true); },
            analysis: function(walletId){ return Routing.generate('app_apiadmin_wallet_getwalletanalyze', { 'walletId': walletId }, true); },
            banks: function(walletId){ return Routing.generate('app_apiadmin_wallet_getwalletbanks', { 'walletId': walletId }, true); },
            postBank: function(walletId){ return Routing.generate('app_apiadmin_wallet_postwalletbank', { 'walletId': walletId }, true); },
            postAddress: function(walletId){ return Routing.generate('app_apiadmin_wallet_postuserwalletaddress', { 'walletId': walletId }, true); },
        },
        internalTransfer: {
            get: function(internalTransferId){ return Routing.generate('app_apiadmin_internaltransfer_getinternaltransfer', { 'internalTransferId': internalTransferId }, true); },
            list: function(){ return Routing.generate('app_apiadmin_internaltransfer_getinternaltransfers', {}, true); },
            decline: function(internalTransferId){ return Routing.generate('app_apiadmin_internaltransfer_putinternaltransferdecline', { 'internalTransferId': internalTransferId }, true); },
            approve: function(internalTransferId){ return Routing.generate('app_apiadmin_internaltransfer_putinternaltransferapprove', { 'internalTransferId': internalTransferId }, true); },
            reject: function(internalTransferId){ return Routing.generate('app_apiadmin_internaltransfer_putinternaltransferreject', { 'internalTransferId': internalTransferId }, true); },
            revert: function(internalTransferId){ return Routing.generate('app_apiadmin_internaltransfer_putinternaltransferrevert', { 'internalTransferId': internalTransferId }, true); },
        },
        withdrawal: {
            get: function(withdrawalId){ return Routing.generate('app_apiadmin_withdrawal_getwithdrawal', { 'withdrawalId': withdrawalId }, true); },
            list: function(){ return Routing.generate('app_apiadmin_withdrawal_getwithdrawals', {}, true); },
            decline: function(withdrawalId){ return Routing.generate('app_apiadmin_withdrawal_putwithdrawaldecline', { 'withdrawalId': withdrawalId }, true); },
            approve: function(withdrawalId){ return Routing.generate('app_apiadmin_withdrawal_putwithdrawalapprove', { 'withdrawalId': withdrawalId }, true); },
            reject: function(withdrawalId){ return Routing.generate('app_apiadmin_withdrawal_putwithdrawalreject', { 'withdrawalId': withdrawalId }, true); },
            externalApproval: function(withdrawalId){ return Routing.generate('app_apiadmin_withdrawal_putwithdrawalsendforexternalapproval', { 'withdrawalId': withdrawalId }, true); },
        },
        checkoutOrder: {
            list: function () {
                return Routing.generate('app_api_checkoutorder_getcheckoutorders', {}, true);
            },
        },
        workspace: {
            list: function () {
                return Routing.generate('app_api_workspace_getworkspaces', {}, true);
            },
        },
        employee: {
            list: function () {
                return Routing.generate('app_api_employee_getemployees', {}, true);
            },
        },
        POSOrder: {
            list: function () {
                return Routing.generate('app_apiadmin_posorder_getposorders', {}, true);
            },
        },
        order: {
            list: function(){ return Routing.generate('app_apiadmin_order_getorders', {}, true); },
            post: function(){ return Routing.generate('app_api_order_postorder', {}, true); },
        },
        trade: {
            list: function () { return Routing.generate('app_apiadmin_trade_gettrades', {}, true); }
        },
        user: {
            get: function(userId){ return Routing.generate('app_apiadmin_user_getsingleuser', { 'userId': userId }, true); },
            me: function(){ return Routing.generate('app_api_user_getuserlogged', {}, true); },
            list: function(){ return Routing.generate('app_apiadmin_user_getusers', {}, true); },
            voterRoles: function(userId){ return Routing.generate('app_apiadmin_user_getuservoterroles', { 'userId': userId }, true); },
            grant: function(userId, voterRoleId){ return Routing.generate('app_apiadmin_user_putuservoterrolesgrant', { 'userId': userId, 'voterRoleId': voterRoleId }, true); },
            deny: function(userId, voterRoleId){ return Routing.generate('app_apiadmin_user_putuservoterrolesdeny', { 'userId': userId, 'voterRoleId': voterRoleId }, true); },
            apiKeys: function(userId){ return Routing.generate('app_apiadmin_user_getuserapikeys', { 'userId': userId }, true); },
            apiKeyDeactivate: function(userId, key){ return Routing.generate('app_apiadmin_user_putuserapikeydeactivate', { 'userId': userId, 'key': key }, true); },
            updateData: function(userId){ return Routing.generate('app_apiadmin_user_putuserupdatedata', { 'userId': userId }, true); },
            setVerificationStatus: function(userId, status){ return Routing.generate('app_apiadmin_user_putverificationstatus', { 'userId': userId, 'status': status }, true); },
            removeUser: function(userId, status){ return Routing.generate('app_apiadmin_user_patchremoveuser', { 'userId': userId, 'status': status }, true); },
            banks: function(userId){ return Routing.generate('app_apiadmin_user_getuserbanks', { 'userId': userId }, true); },
            assignTag: function(userId, tag){ return Routing.generate('app_apiadmin_user_putusertagassign', { 'userId': userId, 'tag': tag }, true); },
            unassignTag: function(userId, tag){ return Routing.generate('app_apiadmin_user_putusertagunassign', { 'userId': userId, 'tag': tag }, true); },
            toggleEmailConfirmed: function(userId){ return Routing.generate('app_apiadmin_user_putusertoggleemailconfirmed', { 'userId': userId }, true); },
            toggleTradingEnabled: function(userId){ return Routing.generate('app_apiadmin_user_putusertoggletradingenabled', { 'userId': userId }, true); },
            resendConfirmation: function(userId){ return Routing.generate('app_apiadmin_user_putuserresendconfirmation', { 'userId': userId }, true); },
            loginHistory: function(userId){ return Routing.generate('app_apiadmin_user_getuserloginhistory', { 'userId': userId }, true); },
            pendingOrders: function(userId){ return Routing.generate('app_apiadmin_user_getuserpendingorders', { 'userId': userId }, true); },
            deposits: function(userId){ return Routing.generate('app_apiadmin_user_getuserdeposits', { 'userId': userId }, true); },
            withdrawals: function(userId){ return Routing.generate('app_apiadmin_user_getuserwithdrawals', { 'userId': userId }, true); },
            resendEmailNotification: function(userId, notificationType){ return Routing.generate('app_apiadmin_user_putuserresendemailnotification', { 'userId': userId, 'notificationType': notificationType }, true); },
            disableGAuth: function(userId){ return Routing.generate('app_apiadmin_user_putusergauthdisable', { 'userId': userId }, true); },
            postBank: function(userId){ return Routing.generate('app_apiadmin_user_postuserbank', { 'userId': userId }, true); },
            todayStatistics: function(){ return Routing.generate('app_apiadmin_user_gettodaystatistics', {}, true); },
            pepInfo: function(userId){ return Routing.generate('app_apiadmin_user_getuserpepinfo', { 'userId': userId }, true); },
        },
        financialReports:{
            liquidityReports: function(){ return Routing.generate('app_apiadmin_financialreports_getliquidityreports', {}, true); },
            balances: function(){ return Routing.generate('app_apiadmin_financialreports_getbalances', {}, true); },
            incomingFees: function(){ return Routing.generate('app_apiadmin_financialreports_getincomingfees', {}, true); },
            liquidityBalances: function(){ return Routing.generate('app_apiadmin_financialreports_getliquiditybalances', {}, true); },
        },
        liquidity: {
            liquidityTransactions: function(currencyPairId){ return Routing.generate('app_apiadmin_liquidity_getliquiditytransactions', { 'currencyPairId': currencyPairId }, true); },
        },
        deposit:{
            get: function(depositId){ return Routing.generate('app_apiadmin_deposit_getdeposit', { 'depositId': depositId }, true); },
            list: function (){ return Routing.generate('app_apiadmin_deposit_getdeposits', {}, true); },
            post: function(){ return Routing.generate('app_apiadmin_deposit_postdeposit', {}, true); },
            approve: function(depositId){ return Routing.generate('app_apiadmin_deposit_putdepositapprove', { 'depositId': depositId }, true); },
            decline: function(depositId){ return Routing.generate('app_apiadmin_deposit_putdepositdecline', { 'depositId': depositId }, true); },
            revert: function(depositId){ return Routing.generate('app_apiadmin_deposit_putdepositrevert', { 'depositId': depositId }, true); },
            blockchainTx: function(depositId){ return Routing.generate('app_apiadmin_deposit_getdepositblockchaintx', { 'depositId': depositId }, true); },
        },
        voterRole: {
            list: function (){ return Routing.generate('app_apiadmin_voterrole_getvoterroles', {}, true); },
            post: function(){ return Routing.generate('app_apiadmin_voterrole_postvoterrole', {}, true); },
        },
        systemTag: {
            list: function (){ return Routing.generate('app_apiadmin_systemtag_getsystemtags', {}, true); },
            toggle: function(systemTagId){ return Routing.generate('app_apiadmin_systemtag_putsystemtagtoggle', { 'systemTagId': systemTagId }, true); },
        },
        currency: {
            types: function (){ return Routing.generate('app_api_currency_getcurrencytypes', {}, true); },
            list: function (){ return Routing.generate('app_apiadmin_currency_getcurrencies', {}, true); },
            post: function(){ return Routing.generate('app_apiadmin_currency_postcurrency', {}, true); },
            disable: function (currencyId){ return Routing.generate('app_apiadmin_currency_putcurrencydisable', { 'currencyId': currencyId }, true); },
            enable: function (currencyId){ return Routing.generate('app_apiadmin_currency_putcurrencyenable', { 'currencyId': currencyId }, true); },
            fee: function (currencyId){ return Routing.generate('app_apiadmin_currency_putcurrencyfee', { 'currencyId': currencyId }, true); }
        },
        currencyPair: {
            list: function(){ return Routing.generate('app_apiadmin_currencypair_getcurrencypairs', {}, true); },
            post: function(){ return Routing.generate('app_apiadmin_currencypair_postcurrencypair', {}, true); },
            disable: function (currencyPairId){ return Routing.generate('app_apiadmin_currencypair_putcurrencypairdisable', { 'currencyPairId': currencyPairId }, true); },
            enable: function (currencyPairId){ return Routing.generate('app_apiadmin_currencypair_putcurrencypairenable', { 'currencyPairId': currencyPairId }, true); }
        },
        transfer: {
            list: {
                byWallet: function(walletId){ return Routing.generate('app_apiadmin_transfer_gettransfersbywallet', { 'walletId': walletId }, true); }
            }
        },
        tradingTransaction: {
            list: {
                notProcessed: function(){ return Routing.generate('app_apiadmin_tradingtransaction_gettradingtransactionsnotprocessed', {}, true); },
            }
        },
        walletBalance: {
            list: {
                byWallet: function(walletId){ return Routing.generate('app_apiadmin_walletbalance_getwalletbalancesbywallet', { 'walletId': walletId }, true); }
            }
        },
        walletTransfer: {
            one: {
                byTrade: function(tradeId){ return Routing.generate('app_apiadmin_wallettransfer_getwallettransferbytrade', { 'tradeId': tradeId }, true); }
            },
            list: {
                byOrder: function(orderId){ return Routing.generate('app_apiadmin_wallettransfer_getwallettransfersbyorder', { 'orderId': orderId }, true); },
                notProcessed: function(){ return Routing.generate('app_apiadmin_wallettransfer_getwallettransfersnotprocessed', {}, true); },
            }
        },
        giifReports: {
            list: function () {
                return Routing.generate('app_apiadmin_giifreport_getgiifreports', {}, true);
            },
        },
    }
});
