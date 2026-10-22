import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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

/**
* @see \App\Http\Controllers\LandController::storePlot
* @see app/Http/Controllers/LandController.php:84
* @route '/land/plots'
*/
export const storePlot = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storePlot.url(options),
    method: 'post',
})

storePlot.definition = {
    methods: ["post"],
    url: '/land/plots',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::storePlot
* @see app/Http/Controllers/LandController.php:84
* @route '/land/plots'
*/
storePlot.url = (options?: RouteQueryOptions) => {
    return storePlot.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::storePlot
* @see app/Http/Controllers/LandController.php:84
* @route '/land/plots'
*/
storePlot.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storePlot.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::storePlot
* @see app/Http/Controllers/LandController.php:84
* @route '/land/plots'
*/
const storePlotForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storePlot.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::storePlot
* @see app/Http/Controllers/LandController.php:84
* @route '/land/plots'
*/
storePlotForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storePlot.url(options),
    method: 'post',
})

storePlot.form = storePlotForm

/**
* @see \App\Http\Controllers\LandController::updatePlot
* @see app/Http/Controllers/LandController.php:92
* @route '/land/plots/{plot}'
*/
export const updatePlot = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePlot.url(args, options),
    method: 'put',
})

updatePlot.definition = {
    methods: ["put"],
    url: '/land/plots/{plot}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LandController::updatePlot
* @see app/Http/Controllers/LandController.php:92
* @route '/land/plots/{plot}'
*/
updatePlot.url = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updatePlot.definition.url
            .replace('{plot}', parsedArgs.plot.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::updatePlot
* @see app/Http/Controllers/LandController.php:92
* @route '/land/plots/{plot}'
*/
updatePlot.put = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePlot.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LandController::updatePlot
* @see app/Http/Controllers/LandController.php:92
* @route '/land/plots/{plot}'
*/
const updatePlotForm = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePlot.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::updatePlot
* @see app/Http/Controllers/LandController.php:92
* @route '/land/plots/{plot}'
*/
updatePlotForm.put = (args: { plot: number | { id: number } } | [plot: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePlot.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updatePlot.form = updatePlotForm

/**
* @see \App\Http\Controllers\LandController::storeSeason
* @see app/Http/Controllers/LandController.php:100
* @route '/land/seasons'
*/
export const storeSeason = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeSeason.url(options),
    method: 'post',
})

storeSeason.definition = {
    methods: ["post"],
    url: '/land/seasons',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::storeSeason
* @see app/Http/Controllers/LandController.php:100
* @route '/land/seasons'
*/
storeSeason.url = (options?: RouteQueryOptions) => {
    return storeSeason.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::storeSeason
* @see app/Http/Controllers/LandController.php:100
* @route '/land/seasons'
*/
storeSeason.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeSeason.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::storeSeason
* @see app/Http/Controllers/LandController.php:100
* @route '/land/seasons'
*/
const storeSeasonForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeSeason.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::storeSeason
* @see app/Http/Controllers/LandController.php:100
* @route '/land/seasons'
*/
storeSeasonForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeSeason.url(options),
    method: 'post',
})

storeSeason.form = storeSeasonForm

/**
* @see \App\Http\Controllers\LandController::updateSeason
* @see app/Http/Controllers/LandController.php:115
* @route '/land/seasons/{season}'
*/
export const updateSeason = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateSeason.url(args, options),
    method: 'put',
})

updateSeason.definition = {
    methods: ["put"],
    url: '/land/seasons/{season}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LandController::updateSeason
* @see app/Http/Controllers/LandController.php:115
* @route '/land/seasons/{season}'
*/
updateSeason.url = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateSeason.definition.url
            .replace('{season}', parsedArgs.season.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::updateSeason
* @see app/Http/Controllers/LandController.php:115
* @route '/land/seasons/{season}'
*/
updateSeason.put = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateSeason.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LandController::updateSeason
* @see app/Http/Controllers/LandController.php:115
* @route '/land/seasons/{season}'
*/
const updateSeasonForm = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateSeason.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::updateSeason
* @see app/Http/Controllers/LandController.php:115
* @route '/land/seasons/{season}'
*/
updateSeasonForm.put = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateSeason.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateSeason.form = updateSeasonForm

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:128
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
* @see app/Http/Controllers/LandController.php:128
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
* @see app/Http/Controllers/LandController.php:128
* @route '/land/seasons/{season}/harvest'
*/
harvest.post = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: harvest.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:128
* @route '/land/seasons/{season}/harvest'
*/
const harvestForm = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: harvest.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::harvest
* @see app/Http/Controllers/LandController.php:128
* @route '/land/seasons/{season}/harvest'
*/
harvestForm.post = (args: { season: number | { id: number } } | [season: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: harvest.url(args, options),
    method: 'post',
})

harvest.form = harvestForm

/**
* @see \App\Http\Controllers\LandController::storeItem
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
export const storeItem = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeItem.url(options),
    method: 'post',
})

storeItem.definition = {
    methods: ["post"],
    url: '/land/items',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LandController::storeItem
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
storeItem.url = (options?: RouteQueryOptions) => {
    return storeItem.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::storeItem
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
storeItem.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeItem.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::storeItem
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
const storeItemForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeItem.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::storeItem
* @see app/Http/Controllers/LandController.php:58
* @route '/land/items'
*/
storeItemForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeItem.url(options),
    method: 'post',
})

storeItem.form = storeItemForm

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
export const bigha = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: bigha.url(options),
    method: 'put',
})

bigha.definition = {
    methods: ["put"],
    url: '/setup/land/bigha',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
bigha.url = (options?: RouteQueryOptions) => {
    return bigha.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
bigha.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: bigha.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
const bighaForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bigha.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
bighaForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bigha.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

bigha.form = bighaForm

const LandController = { index, season, storePlot, updatePlot, storeSeason, updateSeason, harvest, storeItem, bigha }

export default LandController