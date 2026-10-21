import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/stock/move',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::create
* @see app/Http/Controllers/StockController.php:51
* @route '/stock/move'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\StockController::issue
* @see app/Http/Controllers/StockController.php:100
* @route '/stock/issue'
*/
export const issue = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: issue.url(options),
    method: 'post',
})

issue.definition = {
    methods: ["post"],
    url: '/stock/issue',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StockController::issue
* @see app/Http/Controllers/StockController.php:100
* @route '/stock/issue'
*/
issue.url = (options?: RouteQueryOptions) => {
    return issue.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::issue
* @see app/Http/Controllers/StockController.php:100
* @route '/stock/issue'
*/
issue.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: issue.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::issue
* @see app/Http/Controllers/StockController.php:100
* @route '/stock/issue'
*/
const issueForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: issue.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::issue
* @see app/Http/Controllers/StockController.php:100
* @route '/stock/issue'
*/
issueForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: issue.url(options),
    method: 'post',
})

issue.form = issueForm

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/stock',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:27
* @route '/stock'
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
* @see \App\Http\Controllers\StockController::purchase
* @see app/Http/Controllers/StockController.php:71
* @route '/stock/purchase'
*/
export const purchase = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: purchase.url(options),
    method: 'post',
})

purchase.definition = {
    methods: ["post"],
    url: '/stock/purchase',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StockController::purchase
* @see app/Http/Controllers/StockController.php:71
* @route '/stock/purchase'
*/
purchase.url = (options?: RouteQueryOptions) => {
    return purchase.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::purchase
* @see app/Http/Controllers/StockController.php:71
* @route '/stock/purchase'
*/
purchase.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: purchase.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::purchase
* @see app/Http/Controllers/StockController.php:71
* @route '/stock/purchase'
*/
const purchaseForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: purchase.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::purchase
* @see app/Http/Controllers/StockController.php:71
* @route '/stock/purchase'
*/
purchaseForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: purchase.url(options),
    method: 'post',
})

purchase.form = purchaseForm

/**
* @see \App\Http\Controllers\StockController::produce
* @see app/Http/Controllers/StockController.php:120
* @route '/stock/produce'
*/
export const produce = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: produce.url(options),
    method: 'post',
})

produce.definition = {
    methods: ["post"],
    url: '/stock/produce',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StockController::produce
* @see app/Http/Controllers/StockController.php:120
* @route '/stock/produce'
*/
produce.url = (options?: RouteQueryOptions) => {
    return produce.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::produce
* @see app/Http/Controllers/StockController.php:120
* @route '/stock/produce'
*/
produce.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: produce.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::produce
* @see app/Http/Controllers/StockController.php:120
* @route '/stock/produce'
*/
const produceForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: produce.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::produce
* @see app/Http/Controllers/StockController.php:120
* @route '/stock/produce'
*/
produceForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: produce.url(options),
    method: 'post',
})

produce.form = produceForm

const stock = {
    create: Object.assign(create, create),
    issue: Object.assign(issue, issue),
    index: Object.assign(index, index),
    purchase: Object.assign(purchase, purchase),
    produce: Object.assign(produce, produce),
}

export default stock