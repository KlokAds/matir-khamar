import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import connectionDdf068 from './connection'
/**
* @see \App\Http\Controllers\ImportController::connection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
export const connection = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: connection.url(options),
    method: 'put',
})

connection.definition = {
    methods: ["put"],
    url: '/setup/import/connection',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ImportController::connection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
connection.url = (options?: RouteQueryOptions) => {
    return connection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::connection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
connection.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: connection.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ImportController::connection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
const connectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: connection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::connection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
connectionForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: connection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

connection.form = connectionForm

/**
* @see \App\Http\Controllers\ImportController::fetch
* @see app/Http/Controllers/ImportController.php:149
* @route '/setup/import/fetch'
*/
export const fetch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fetch.url(options),
    method: 'post',
})

fetch.definition = {
    methods: ["post"],
    url: '/setup/import/fetch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::fetch
* @see app/Http/Controllers/ImportController.php:149
* @route '/setup/import/fetch'
*/
fetch.url = (options?: RouteQueryOptions) => {
    return fetch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::fetch
* @see app/Http/Controllers/ImportController.php:149
* @route '/setup/import/fetch'
*/
fetch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: fetch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::fetch
* @see app/Http/Controllers/ImportController.php:149
* @route '/setup/import/fetch'
*/
const fetchForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: fetch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::fetch
* @see app/Http/Controllers/ImportController.php:149
* @route '/setup/import/fetch'
*/
fetchForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: fetch.url(options),
    method: 'post',
})

fetch.form = fetchForm

/**
* @see \App\Http\Controllers\ImportController::post
* @see app/Http/Controllers/ImportController.php:168
* @route '/setup/import/post'
*/
export const post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(options),
    method: 'post',
})

post.definition = {
    methods: ["post"],
    url: '/setup/import/post',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::post
* @see app/Http/Controllers/ImportController.php:168
* @route '/setup/import/post'
*/
post.url = (options?: RouteQueryOptions) => {
    return post.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::post
* @see app/Http/Controllers/ImportController.php:168
* @route '/setup/import/post'
*/
post.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: post.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::post
* @see app/Http/Controllers/ImportController.php:168
* @route '/setup/import/post'
*/
const postForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: post.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::post
* @see app/Http/Controllers/ImportController.php:168
* @route '/setup/import/post'
*/
postForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: post.url(options),
    method: 'post',
})

post.form = postForm

/**
* @see \App\Http\Controllers\ImportController::mapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
export const mapping = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: mapping.url(args, options),
    method: 'put',
})

mapping.definition = {
    methods: ["put"],
    url: '/setup/import/mappings/{mapping}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ImportController::mapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
mapping.url = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mapping: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { mapping: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            mapping: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        mapping: typeof args.mapping === 'object'
        ? args.mapping.id
        : args.mapping,
    }

    return mapping.definition.url
            .replace('{mapping}', parsedArgs.mapping.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::mapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
mapping.put = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: mapping.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ImportController::mapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
const mappingForm = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: mapping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::mapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
mappingForm.put = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: mapping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

mapping.form = mappingForm

/**
* @see \App\Http\Controllers\ImportController::resolve
* @see app/Http/Controllers/ImportController.php:227
* @route '/setup/import/records/{record}/resolve'
*/
export const resolve = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(args, options),
    method: 'post',
})

resolve.definition = {
    methods: ["post"],
    url: '/setup/import/records/{record}/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::resolve
* @see app/Http/Controllers/ImportController.php:227
* @route '/setup/import/records/{record}/resolve'
*/
resolve.url = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { record: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { record: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            record: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        record: typeof args.record === 'object'
        ? args.record.id
        : args.record,
    }

    return resolve.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::resolve
* @see app/Http/Controllers/ImportController.php:227
* @route '/setup/import/records/{record}/resolve'
*/
resolve.post = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::resolve
* @see app/Http/Controllers/ImportController.php:227
* @route '/setup/import/records/{record}/resolve'
*/
const resolveForm = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::resolve
* @see app/Http/Controllers/ImportController.php:227
* @route '/setup/import/records/{record}/resolve'
*/
resolveForm.post = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(args, options),
    method: 'post',
})

resolve.form = resolveForm

/**
* @see \App\Http\Controllers\ImportController::qty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
export const qty = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: qty.url(args, options),
    method: 'post',
})

qty.definition = {
    methods: ["post"],
    url: '/setup/import/records/{record}/qty',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::qty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
qty.url = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { record: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { record: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            record: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        record: typeof args.record === 'object'
        ? args.record.id
        : args.record,
    }

    return qty.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::qty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
qty.post = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: qty.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::qty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
const qtyForm = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: qty.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::qty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
qtyForm.post = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: qty.url(args, options),
    method: 'post',
})

qty.form = qtyForm

/**
* @see \App\Http\Controllers\ImportController::ignore
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
export const ignore = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ignore.url(options),
    method: 'post',
})

ignore.definition = {
    methods: ["post"],
    url: '/setup/import/ignore-undecided',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::ignore
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
ignore.url = (options?: RouteQueryOptions) => {
    return ignore.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::ignore
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
ignore.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ignore.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::ignore
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
const ignoreForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ignore.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::ignore
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
ignoreForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ignore.url(options),
    method: 'post',
})

ignore.form = ignoreForm

const importMethod = {
    connection: Object.assign(connection, connectionDdf068),
    fetch: Object.assign(fetch, fetch),
    post: Object.assign(post, post),
    mapping: Object.assign(mapping, mapping),
    resolve: Object.assign(resolve, resolve),
    qty: Object.assign(qty, qty),
    ignore: Object.assign(ignore, ignore),
}

export default importMethod