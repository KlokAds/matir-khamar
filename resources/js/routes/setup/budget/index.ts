import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::update
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
export const update = (args: { head: string | number | { id: string | number } } | [head: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/setup/budget/{head}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ReportController::update
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
update.url = (args: { head: string | number | { id: string | number } } | [head: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { head: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { head: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            head: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        head: typeof args.head === 'object'
        ? args.head.id
        : args.head,
    }

    return update.definition.url
            .replace('{head}', parsedArgs.head.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::update
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
update.put = (args: { head: string | number | { id: string | number } } | [head: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReportController::update
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
const updateForm = (args: { head: string | number | { id: string | number } } | [head: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReportController::update
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
updateForm.put = (args: { head: string | number | { id: string | number } } | [head: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const budget = {
    update: Object.assign(update, update),
}

export default budget