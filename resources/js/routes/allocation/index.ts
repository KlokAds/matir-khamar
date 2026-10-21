import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/allocation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AllocationController::create
* @see app/Http/Controllers/AllocationController.php:20
* @route '/allocation'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\AllocationController::store
* @see app/Http/Controllers/AllocationController.php:45
* @route '/allocation'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/allocation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AllocationController::store
* @see app/Http/Controllers/AllocationController.php:45
* @route '/allocation'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AllocationController::store
* @see app/Http/Controllers/AllocationController.php:45
* @route '/allocation'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AllocationController::store
* @see app/Http/Controllers/AllocationController.php:45
* @route '/allocation'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AllocationController::store
* @see app/Http/Controllers/AllocationController.php:45
* @route '/allocation'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const allocation = {
    create: Object.assign(create, create),
    store: Object.assign(store, store),
}

export default allocation