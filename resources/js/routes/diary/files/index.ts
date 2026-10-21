import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
export const show = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/diary/files/{file}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
show.url = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { file: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { file: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            file: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        file: typeof args.file === 'object'
        ? args.file.id
        : args.file,
    }

    return show.definition.url
            .replace('{file}', parsedArgs.file.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
show.get = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
show.head = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
const showForm = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
showForm.get = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::show
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
showForm.head = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const files = {
    show: Object.assign(show, show),
}

export default files