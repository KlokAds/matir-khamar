import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import plots from './plots'
import seasons from './seasons'
import items from './items'
/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/land',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LandController::index
* @see app/Http/Controllers/LandController.php:26
* @route '/land'
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
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
export const season = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: season.url(args, options),
    method: 'get',
})

season.definition = {
    methods: ["get","head"],
    url: '/land/seasons/{season}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
season.url = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return season.definition.url
            .replace('{season}', parsedArgs.season.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
season.get = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: season.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
season.head = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: season.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
const seasonForm = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: season.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
seasonForm.get = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: season.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LandController::season
* @see app/Http/Controllers/LandController.php:43
* @route '/land/seasons/{season}'
*/
seasonForm.head = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: season.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

season.form = seasonForm

const land = {
    index: Object.assign(index, index),
    season: Object.assign(season, season),
    plots: Object.assign(plots, plots),
    seasons: Object.assign(seasons, seasons),
    items: Object.assign(items, items),
}

export default land