import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/land/items',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const items = {
    store: Object.assign(store, store),
}

export default items