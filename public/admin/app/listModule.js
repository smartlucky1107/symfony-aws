let listModule = angular.module('listModule', []);
listModule.factory('listManager', function() {
    let options = {
        'page': 1,
        'pageSize': 100,
        'sortBy': 'id',
        'sortType': 0
    };

    let filters = {};

    let sortTypeToggle = function(){
        if(options.sortType === 0){
            options.sortType = 1;
        }else{
            options.sortType = 0;
        }
    };

    return {
        initFilterFields: function(fields){
            angular.forEach(fields, function(field){
                options[field] = null;
                filters[field] = null;
            });
        },
        initSortBy: function(field){
            options.sortBy = field;
        },
        options: options,
        filters: filters,
        sortTypeToggle: sortTypeToggle,
        filtersApply: function(){
            angular.forEach(filters, function(filterValue, filterKey){
                angular.forEach(options, function(optionValue, optionKey){
                    if(optionKey === filterKey){
                        if(filterValue === 0 || filterValue){
                            options[optionKey] = filterValue;
                        }else{
                            options[optionKey] = null;
                        }
                    }
                });
            });
        },
        isSortByField: function(field){
            if(field === options.sortBy){
                return true;
            }

            return false;
        },
        isSortBy: function(field, type){
            if(field === options.sortBy && type === options.sortType){
                return true;
            }

            return false;
        },
        isPageActive: function(page){
            if(page === options.page){
                return true;
            }

            return false;
        },
        isPageSizeActive: function(pageSize){
           if(pageSize === options.pageSize){
               return true;
           }

           return false;
        },
        setSortBy: function(field, callback){
            if(options.sortBy === field){
                sortTypeToggle();
                callback();
            }else{
                options.sortBy = field;
                callback();
            }
        },
        changePage: function(page, callback){
            options.page = page;
            callback();
        },
        changePageSize: function(pageSize, callback){
            options.pageSize = pageSize;
            callback();
        },
        processResult: function(result){
            options.page = result.page;
            options.pageSize = result.pageSize;
        },
        generatePages: function(result){
            let pages = [];
            let pagesNum = Math.ceil(result.totalItems / result.pageSize);
            for (let i = 1; i <= pagesNum; i++) {
                pages.push({'page': i});
            }

            return pages;
        }
    }
});
