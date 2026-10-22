import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:99
* @route '/land/seasons'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/land/seasons',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:99
* @route '/land/seasons'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:99
* @route '/land/seasons'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:99
* @route '/land/seasons'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::store
* @see app/Http/Controllers/LandController.php:99
* @route '/land/seasons'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:114
* @route '/land/seasons/{season}'
*/
export const update = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/land/seasons/{season}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:114
* @route '/land/seasons/{season}'
*/
update.url = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { season: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { season: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            season: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        season: typeof args.season === 'object'
        ? args.season.id
        : args.season,
    }

    return update.definition.url
            .replace('{season}', parsedArgs.season.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:114
* @route '/land/seasons/{season}'
*/
update.put = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LandController::update
* @see app/Http/Controllers/LandController.php:114
* @route '/land/seasons/{season}'
*/
const updateForm = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see app/Http/Controllers/LandController.php:114
* @route '/land/seasons/{season}'
*/
updateForm.put = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:127
* @route '/land/seasons/{season}/harvest'
*/
export const harvest = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: harvest.url(args, options),
    method: 'post',
})

harvest.definition = {
    methods: ["post"],
    url: '/land/seasons/{season}/harvest',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:127
* @route '/land/seasons/{season}/harvest'
*/
harvest.url = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { season: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { season: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            season: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        season: typeof args.season === 'object'
        ? args.season.id
        : args.season,
    }

    return harvest.definition.url
            .replace('{season}', parsedArgs.season.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:127
* @route '/land/seasons/{season}/harvest'
*/
harvest.post = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: harvest.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:127
* @route '/land/seasons/{season}/harvest'
*/
const harvestForm = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: harvest.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:127
* @route '/land/seasons/{season}/harvest'
*/
harvestForm.post = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: harvest.url(args, options),
    method: 'post',
})

harvest.form = harvestForm

const seasons = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    harvest: Object.assign(harvest, harvest),
}

export default seasons