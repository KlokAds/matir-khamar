import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:83
* @route '/land/plots'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/land/plots',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:83
* @route '/land/plots'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:83
* @route '/land/plots'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:83
* @route '/land/plots'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:83
* @route '/land/plots'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:91
* @route '/land/plots/{plot}'
*/
export const update = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/land/plots/{plot}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:91
* @route '/land/plots/{plot}'
*/
update.url = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { plot: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { plot: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            plot: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        plot: typeof args.plot === 'object'
        ? args.plot.id
        : args.plot,
    }

    return update.definition.url
            .replace('{plot}', parsedArgs.plot.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:91
* @route '/land/plots/{plot}'
*/
update.put = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:91
* @route '/land/plots/{plot}'
*/
const updateForm = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:91
* @route '/land/plots/{plot}'
*/
updateForm.put = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const plots = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default plots