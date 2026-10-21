import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/centers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
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
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
export const show = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/centers/{center}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
show.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
show.get = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
show.head = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
const showForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
showForm.get = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
showForm.head = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

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

const CostCenterController = { index, show, store, update }

export default CostCenterController