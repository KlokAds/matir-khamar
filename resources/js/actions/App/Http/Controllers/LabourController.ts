import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LabourController::storeLogs
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
export const storeLogs = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLogs.url(options),
    method: 'post',
})

storeLogs.definition = {
    methods: ["post"],
    url: '/labour/logs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LabourController::storeLogs
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
storeLogs.url = (options?: RouteQueryOptions) => {
    return storeLogs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::storeLogs
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
storeLogs.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeLogs.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::storeLogs
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
const storeLogsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeLogs.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::storeLogs
* @see app/Http/Controllers/LabourController.php:55
* @route '/labour/logs'
*/
storeLogsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeLogs.url(options),
    method: 'post',
})

storeLogs.form = storeLogsForm

/**
* @see \App\Http\Controllers\LabourController::destroyLog
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
export const destroyLog = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyLog.url(args, options),
    method: 'delete',
})

destroyLog.definition = {
    methods: ["delete"],
    url: '/labour/logs/{log}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\LabourController::destroyLog
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
destroyLog.url = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroyLog.definition.url
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::destroyLog
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
destroyLog.delete = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyLog.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\LabourController::destroyLog
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
const destroyLogForm = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyLog.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::destroyLog
* @see app/Http/Controllers/LabourController.php:81
* @route '/labour/logs/{log}'
*/
destroyLogForm.delete = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyLog.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroyLog.form = destroyLogForm

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/labour',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
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
* @see \App\Http\Controllers\LabourController::storeWorker
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
export const storeWorker = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWorker.url(options),
    method: 'post',
})

storeWorker.definition = {
    methods: ["post"],
    url: '/labour/workers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LabourController::storeWorker
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
storeWorker.url = (options?: RouteQueryOptions) => {
    return storeWorker.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::storeWorker
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
storeWorker.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeWorker.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::storeWorker
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
const storeWorkerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeWorker.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::storeWorker
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
storeWorkerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeWorker.url(options),
    method: 'post',
})

storeWorker.form = storeWorkerForm

/**
* @see \App\Http\Controllers\LabourController::updateWorker
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
export const updateWorker = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateWorker.url(args, options),
    method: 'put',
})

updateWorker.definition = {
    methods: ["put"],
    url: '/labour/workers/{worker}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LabourController::updateWorker
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
updateWorker.url = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { worker: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { worker: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            worker: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        worker: typeof args.worker === 'object'
        ? args.worker.id
        : args.worker,
    }

    return updateWorker.definition.url
            .replace('{worker}', parsedArgs.worker.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::updateWorker
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
updateWorker.put = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateWorker.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LabourController::updateWorker
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
const updateWorkerForm = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateWorker.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::updateWorker
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
updateWorkerForm.put = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateWorker.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateWorker.form = updateWorkerForm

/**
* @see \App\Http\Controllers\LabourController::pay
* @see app/Http/Controllers/LabourController.php:90
* @route '/labour/workers/{worker}/pay'
*/
export const pay = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

pay.definition = {
    methods: ["post"],
    url: '/labour/workers/{worker}/pay',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LabourController::pay
* @see app/Http/Controllers/LabourController.php:90
* @route '/labour/workers/{worker}/pay'
*/
pay.url = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { worker: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { worker: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            worker: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        worker: typeof args.worker === 'object'
        ? args.worker.id
        : args.worker,
    }

    return pay.definition.url
            .replace('{worker}', parsedArgs.worker.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::pay
* @see app/Http/Controllers/LabourController.php:90
* @route '/labour/workers/{worker}/pay'
*/
pay.post = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::pay
* @see app/Http/Controllers/LabourController.php:90
* @route '/labour/workers/{worker}/pay'
*/
const payForm = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::pay
* @see app/Http/Controllers/LabourController.php:90
* @route '/labour/workers/{worker}/pay'
*/
payForm.post = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: pay.url(args, options),
    method: 'post',
})

pay.form = payForm

const LabourController = { storeLogs, destroyLog, index, storeWorker, updateWorker, pay }

export default LabourController