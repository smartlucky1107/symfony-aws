function ServiceCookieStorage($cookies) {
    return {
        authToken: {
            load: function(){
                return $cookies.get('authToken');
            },
            save: function(token){
                $cookies.put('authToken', token);
            },
        },
        redirectUrl: {
            load: function () {
                return $cookies.get('redirectUrl');
            },
            save: function (url) {
                $cookies.put('redirectUrl', url);
            },
        }
    };
};