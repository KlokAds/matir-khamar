import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
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

const money = {
    store: Object.assign(store, store),
}

export default money