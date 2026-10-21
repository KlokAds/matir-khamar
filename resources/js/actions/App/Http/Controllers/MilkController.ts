import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/milk',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MilkController::index
* @see app/Http/Controllers/MilkController.php:19
* @route '/milk'
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
* @see \App\Http\Controllers\MilkController::store
* @see app/Http/Controllers/MilkController.php:43
* @route '/milk'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/milk',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MilkController::store
* @see app/Http/Controllers/MilkController.php:43
* @route '/milk'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MilkController::store
* @see app/Http/Controllers/MilkController.php:43
* @route '/milk'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MilkController::store
* @see app/Http/Controllers/MilkController.php:43
* @route '/milk'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MilkController::store
* @see app/Http/Controllers/MilkController.php:43
* @route '/milk'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const MilkController = { index, store }

export default MilkController