import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/investor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
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
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
export const manage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

manage.definition = {
    methods: ["get","head"],
    url: '/setup/investors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
manage.url = (options?: RouteQueryOptions) => {
    return manage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
manage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
manage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
const manageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
manageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::manage
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
manageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

manage.form = manageForm

/**
* @see \App\Http\Controllers\InvestorController::grant
* @see app/Http/Controllers/InvestorController.php:82
* @route '/setup/investors'
*/
export const grant = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grant.url(options),
    method: 'post',
})

grant.definition = {
    methods: ["post"],
    url: '/setup/investors',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorController::grant
* @see app/Http/Controllers/InvestorController.php:82
* @route '/setup/investors'
*/
grant.url = (options?: RouteQueryOptions) => {
    return grant.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorController::grant
* @see app/Http/Controllers/InvestorController.php:82
* @route '/setup/investors'
*/
grant.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grant.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorController::grant
* @see app/Http/Controllers/InvestorController.php:82
* @route '/setup/investors'
*/
const grantForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grant.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorController::grant
* @see app/Http/Controllers/InvestorController.php:82
* @route '/setup/investors'
*/
grantForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grant.url(options),
    method: 'post',
})

grant.form = grantForm

/**
* @see \App\Http\Controllers\InvestorController::revoke
* @see app/Http/Controllers/InvestorController.php:115
* @route '/setup/investors/{access}'
*/
export const revoke = (args: { access: number | { id: number } } | [access: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: revoke.url(args, options),
    method: 'delete',
})

revoke.definition = {
    methods: ["delete"],
    url: '/setup/investors/{access}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\InvestorController::revoke
* @see app/Http/Controllers/InvestorController.php:115
* @route '/setup/investors/{access}'
*/
revoke.url = (args: { access: number | { id: number } } | [access: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { access: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { access: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            access: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        access: typeof args.access === 'object'
        ? args.access.id
        : args.access,
    }

    return revoke.definition.url
            .replace('{access}', parsedArgs.access.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorController::revoke
* @see app/Http/Controllers/InvestorController.php:115
* @route '/setup/investors/{access}'
*/
revoke.delete = (args: { access: number | { id: number } } | [access: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: revoke.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\InvestorController::revoke
* @see app/Http/Controllers/InvestorController.php:115
* @route '/setup/investors/{access}'
*/
const revokeForm = (args: { access: number | { id: number } } | [access: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: revoke.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorController::revoke
* @see app/Http/Controllers/InvestorController.php:115
* @route '/setup/investors/{access}'
*/
revokeForm.delete = (args: { access: number | { id: number } } | [access: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: revoke.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

revoke.form = revokeForm

const InvestorController = { index, manage, grant, revoke }

export default InvestorController