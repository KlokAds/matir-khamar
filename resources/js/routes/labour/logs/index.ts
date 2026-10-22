import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/labour/logs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\LabourController::destroy
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
export const destroy = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/labour/logs/{log}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\LabourController::destroy
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
destroy.url = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { log: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { log: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            log: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        log: typeof args.log === 'object'
        ? args.log.id
        : args.log,
    }

    return destroy.definition.url
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::destroy
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
destroy.delete = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\LabourController::destroy
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
const destroyForm = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::destroy
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
destroyForm.delete = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const logs = {
    store: Object.assign(store, store),
    destroy: Object.assign(destroy, destroy),
}

export default logs