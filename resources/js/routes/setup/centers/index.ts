import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:87
* @route '/setup/centers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/setup/centers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:87
* @route '/setup/centers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:87
* @route '/setup/centers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:87
* @route '/setup/centers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:87
* @route '/setup/centers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:96
* @route '/setup/centers/{center}'
*/
export const update = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/setup/centers/{center}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:96
* @route '/setup/centers/{center}'
*/
update.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return update.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:96
* @route '/setup/centers/{center}'
*/
update.put = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:96
* @route '/setup/centers/{center}'
*/
const updateForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:96
* @route '/setup/centers/{center}'
*/
updateForm.put = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const centers = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default centers