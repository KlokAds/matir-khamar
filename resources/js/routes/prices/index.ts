import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\PriceController::store
* @see app/Http/Controllers/PriceController.php:32
* @route '/prices'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/prices',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PriceController::store
* @see app/Http/Controllers/PriceController.php:32
* @route '/prices'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PriceController::store
* @see app/Http/Controllers/PriceController.php:32
* @route '/prices'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PriceController::store
* @see app/Http/Controllers/PriceController.php:32
* @route '/prices'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PriceController::store
* @see app/Http/Controllers/PriceController.php:32
* @route '/prices'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\PriceController::destroy
* @see app/Http/Controllers/PriceController.php:59
* @route '/prices/{price}'
*/
export const destroy = (args: { price: number | { id: number } } | [price: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/prices/{price}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\PriceController::destroy
* @see app/Http/Controllers/PriceController.php:59
* @route '/prices/{price}'
*/
destroy.url = (args: { price: number | { id: number } } | [price: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { price: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { price: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            price: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        price: typeof args.price === 'object'
        ? args.price.id
        : args.price,
    }

    return destroy.definition.url
            .replace('{price}', parsedArgs.price.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PriceController::destroy
* @see app/Http/Controllers/PriceController.php:59
* @route '/prices/{price}'
*/
destroy.delete = (args: { price: number | { id: number } } | [price: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\PriceController::destroy
* @see app/Http/Controllers/PriceController.php:59
* @route '/prices/{price}'
*/
const destroyForm = (args: { price: number | { id: number } } | [price: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PriceController::destroy
* @see app/Http/Controllers/PriceController.php:59
* @route '/prices/{price}'
*/
destroyForm.delete = (args: { price: number | { id: number } } | [price: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/prices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PriceController::index
* @see app/Http/Controllers/PriceController.php:19
* @route '/prices'
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

const prices = {
    store: Object.assign(store, store),
    destroy: Object.assign(destroy, destroy),
    index: Object.assign(index, index),
}

export default prices