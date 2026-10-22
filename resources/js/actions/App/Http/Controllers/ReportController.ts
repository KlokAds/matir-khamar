import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:27
* @route '/reports'
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
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
export const usage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: usage.url(options),
    method: 'get',
})

usage.definition = {
    methods: ["get","head"],
    url: '/reports/usage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
usage.url = (options?: RouteQueryOptions) => {
    return usage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
usage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: usage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
usage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: usage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
const usageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: usage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
usageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: usage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::usage
* @see app/Http/Controllers/ReportController.php:51
* @route '/reports/usage'
*/
usageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: usage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

usage.form = usageForm

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
export const monthly = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monthly.url(options),
    method: 'get',
})

monthly.definition = {
    methods: ["get","head"],
    url: '/reports/monthly',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
monthly.url = (options?: RouteQueryOptions) => {
    return monthly.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
monthly.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monthly.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
monthly.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monthly.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
const monthlyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monthly.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
monthlyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monthly.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::monthly
* @see app/Http/Controllers/ReportController.php:63
* @route '/reports/monthly'
*/
monthlyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monthly.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

monthly.form = monthlyForm

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:214
* @route '/setup/budget/{head}'
*/
export const updateBudgetHead = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBudgetHead.url(args, options),
    method: 'put',
})

updateBudgetHead.definition = {
    methods: ["put"],
    url: '/setup/budget/{head}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:214
* @route '/setup/budget/{head}'
*/
updateBudgetHead.url = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateBudgetHead.definition.url
            .replace('{head}', parsedArgs.head.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:214
* @route '/setup/budget/{head}'
*/
updateBudgetHead.put = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBudgetHead.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:214
* @route '/setup/budget/{head}'
*/
const updateBudgetHeadForm = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBudgetHead.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:214
* @route '/setup/budget/{head}'
*/
updateBudgetHeadForm.put = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBudgetHead.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateBudgetHead.form = updateBudgetHeadForm

const ReportController = { index, usage, monthly, updateBudgetHead }

export default ReportController