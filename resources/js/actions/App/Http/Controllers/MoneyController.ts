import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/money',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MoneyController::index
* @see app/Http/Controllers/MoneyController.php:24
* @route '/money'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\MoneyController::store
* @see app/Http/Controllers/MoneyController.php:89
* @route '/setup/money'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/setup/money',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MoneyController::store
* @see app/Http/Controllers/MoneyController.php:89
* @route '/setup/money'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MoneyController::store
* @see app/Http/Controllers/MoneyController.php:89
* @route '/setup/money'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MoneyController::store
* @see app/Http/Controllers/MoneyController.php:89
* @route '/setup/money'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MoneyController::store
* @see app/Http/Controllers/MoneyController.php:89
* @route '/setup/money'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const MoneyController = { index, store }

export default MoneyController