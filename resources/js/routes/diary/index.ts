import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import notes from './notes'
import files from './files'
import goals from './goals'
/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/diary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
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

const diary = {
    notes: Object.assign(notes, notes),
    index: Object.assign(index, index),
    files: Object.assign(files, files),
    goals: Object.assign(goals, goals),
}

export default diary