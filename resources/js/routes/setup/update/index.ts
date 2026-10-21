import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SystemController::run
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
export const run = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: run.url(options),
    method: 'post',
})

run.definition = {
    methods: ["post"],
    url: '/setup/update',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SystemController::run
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
run.url = (options?: RouteQueryOptions) => {
    return run.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::run
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
run.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: run.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SystemController::run
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
const runForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: run.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SystemController::run
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
runForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: run.url(options),
    method: 'post',
})

run.form = runForm

const update = {
    run: Object.assign(run, run),
}

export default update