import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/labour/workers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::store
* @see app/Http/Controllers/LabourController.php:39
* @route '/labour/workers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\LabourController::update
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
export const update = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/labour/workers/{worker}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LabourController::update
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
update.url = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{worker}', parsedArgs.worker.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::update
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
update.put = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LabourController::update
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
const updateForm = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LabourController::update
* @see app/Http/Controllers/LabourController.php:47
* @route '/labour/workers/{worker}'
*/
updateForm.put = (args: { worker: number | { id: number } } | [worker: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

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

const workers = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    pay: Object.assign(pay, pay),
}

export default workers