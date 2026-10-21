import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
export const show = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/attachments/{attachment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
show.url = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { attachment: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { attachment: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            attachment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        attachment: typeof args.attachment === 'object'
        ? args.attachment.id
        : args.attachment,
    }

    return show.definition.url
            .replace('{attachment}', parsedArgs.attachment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
show.get = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
show.head = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
const showForm = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
showForm.get = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:372
* @route '/attachments/{attachment}'
*/
showForm.head = (args: { attachment: string | number | { id: string | number } } | [attachment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const attachments = {
    show: Object.assign(show, show),
}

export default attachments