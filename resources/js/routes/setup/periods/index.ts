import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SetupController::lock
* @see app/Http/Controllers/SetupController.php:236
* @route '/setup/periods'
*/
export const lock = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lock.url(options),
    method: 'post',
})

lock.definition = {
    methods: ["post"],
    url: '/setup/periods',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SetupController::lock
* @see app/Http/Controllers/SetupController.php:236
* @route '/setup/periods'
*/
lock.url = (options?: RouteQueryOptions) => {
    return lock.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::lock
* @see app/Http/Controllers/SetupController.php:236
* @route '/setup/periods'
*/
lock.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lock.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::lock
* @see app/Http/Controllers/SetupController.php:236
* @route '/setup/periods'
*/
const lockForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lock.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::lock
* @see app/Http/Controllers/SetupController.php:236
* @route '/setup/periods'
*/
lockForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lock.url(options),
    method: 'post',
})

lock.form = lockForm

/**
* @see \App\Http\Controllers\SetupController::unlock
* @see app/Http/Controllers/SetupController.php:252
* @route '/setup/periods/{lock}'
*/
export const unlock = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unlock.url(args, options),
    method: 'delete',
})

unlock.definition = {
    methods: ["delete"],
    url: '/setup/periods/{lock}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SetupController::unlock
* @see app/Http/Controllers/SetupController.php:252
* @route '/setup/periods/{lock}'
*/
unlock.url = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { lock: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { lock: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            lock: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        lock: typeof args.lock === 'object'
        ? args.lock.id
        : args.lock,
    }

    return unlock.definition.url
            .replace('{lock}', parsedArgs.lock.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::unlock
* @see app/Http/Controllers/SetupController.php:252
* @route '/setup/periods/{lock}'
*/
unlock.delete = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unlock.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\SetupController::unlock
* @see app/Http/Controllers/SetupController.php:252
* @route '/setup/periods/{lock}'
*/
const unlockForm = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlock.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::unlock
* @see app/Http/Controllers/SetupController.php:252
* @route '/setup/periods/{lock}'
*/
unlockForm.delete = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlock.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

unlock.form = unlockForm

const periods = {
    lock: Object.assign(lock, lock),
    unlock: Object.assign(unlock, unlock),
}

export default periods