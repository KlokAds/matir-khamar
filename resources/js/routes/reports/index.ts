import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
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
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
export const budget = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: budget.url(options),
    method: 'get',
})

budget.definition = {
    methods: ["get","head"],
    url: '/reports/budget',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
budget.url = (options?: RouteQueryOptions) => {
    return budget.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
budget.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: budget.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
budget.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: budget.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
const budgetForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budget.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
budgetForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budget.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:194
* @route '/reports/budget'
*/
budgetForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budget.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

budget.form = budgetForm

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

const reports = {
    index: Object.assign(index, index),
    budget: Object.assign(budget, budget),
    usage: Object.assign(usage, usage),
    monthly: Object.assign(monthly, monthly),
}

export default reports