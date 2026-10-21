import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:130
* @route '/setup/items'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/setup/items',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:130
* @route '/setup/items'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:130
* @route '/setup/items'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:130
* @route '/setup/items'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:130
* @route '/setup/items'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:148
* @route '/setup/items/{item}'
*/
export const update = (args: { item: string | number | { id: string | number } } | [item: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/setup/items/{item}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:148
* @route '/setup/items/{item}'
*/
update.url = (args: { item: string | number | { id: string | number } } | [item: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { item: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { item: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            item: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        item: typeof args.item === 'object'
        ? args.item.id
        : args.item,
    }

    return update.definition.url
            .replace('{item}', parsedArgs.item.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:148
* @route '/setup/items/{item}'
*/
update.put = (args: { item: string | number | { id: string | number } } | [item: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:148
* @route '/setup/items/{item}'
*/
const updateForm = (args: { item: string | number | { id: string | number } } | [item: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:148
* @route '/setup/items/{item}'
*/
updateForm.put = (args: { item: string | number | { id: string | number } } | [item: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const items = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default items