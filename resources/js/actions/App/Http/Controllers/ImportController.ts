import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/setup/import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::index
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
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
* @see \App\Http\Controllers\ImportController::saveConnection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
export const saveConnection = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: saveConnection.url(options),
    method: 'put',
})

saveConnection.definition = {
    methods: ["put"],
    url: '/setup/import/connection',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ImportController::saveConnection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
saveConnection.url = (options?: RouteQueryOptions) => {
    return saveConnection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::saveConnection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
saveConnection.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: saveConnection.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ImportController::saveConnection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
const saveConnectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: saveConnection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::saveConnection
* @see app/Http/Controllers/ImportController.php:93
* @route '/setup/import/connection'
*/
saveConnectionForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: saveConnection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

saveConnection.form = saveConnectionForm

/**
* @see \App\Http\Controllers\ImportController::testConnection
* @see app/Http/Controllers/ImportController.php:128
* @route '/setup/import/connection/test'
*/
export const testConnection = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testConnection.url(options),
    method: 'post',
})

testConnection.definition = {
    methods: ["post"],
    url: '/setup/import/connection/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::testConnection
* @see app/Http/Controllers/ImportController.php:128
* @route '/setup/import/connection/test'
*/
testConnection.url = (options?: RouteQueryOptions) => {
    return testConnection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::testConnection
* @see app/Http/Controllers/ImportController.php:128
* @route '/setup/import/connection/test'
*/
testConnection.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testConnection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::testConnection
* @see app/Http/Controllers/ImportController.php:128
* @route '/setup/import/connection/test'
*/
const testConnectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testConnection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::testConnection
* @see app/Http/Controllers/ImportController.php:128
* @route '/setup/import/connection/test'
*/
testConnectionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testConnection.url(options),
    method: 'post',
})

testConnection.form = testConnectionForm

/**
* @see \App\Http\Controllers\ImportController::forgetConnection
* @see app/Http/Controllers/ImportController.php:137
* @route '/setup/import/connection'
*/
export const forgetConnection = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: forgetConnection.url(options),
    method: 'delete',
})

forgetConnection.definition = {
    methods: ["delete"],
    url: '/setup/import/connection',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ImportController::forgetConnection
* @see app/Http/Controllers/ImportController.php:137
* @route '/setup/import/connection'
*/
forgetConnection.url = (options?: RouteQueryOptions) => {
    return forgetConnection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::forgetConnection
* @see app/Http/Controllers/ImportController.php:137
* @route '/setup/import/connection'
*/
forgetConnection.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: forgetConnection.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ImportController::forgetConnection
* @see app/Http/Controllers/ImportController.php:137
* @route '/setup/import/connection'
*/
const forgetConnectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: forgetConnection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::forgetConnection
* @see app/Http/Controllers/ImportController.php:137
* @route '/setup/import/connection'
*/
forgetConnectionForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: forgetConnection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

forgetConnection.form = forgetConnectionForm

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
* @see \App\Http\Controllers\ImportController::updateMapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
export const updateMapping = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateMapping.url(args, options),
    method: 'put',
})

updateMapping.definition = {
    methods: ["put"],
    url: '/setup/import/mappings/{mapping}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ImportController::updateMapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
updateMapping.url = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateMapping.definition.url
            .replace('{mapping}', parsedArgs.mapping.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::updateMapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
updateMapping.put = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateMapping.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ImportController::updateMapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
const updateMappingForm = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateMapping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::updateMapping
* @see app/Http/Controllers/ImportController.php:177
* @route '/setup/import/mappings/{mapping}'
*/
updateMappingForm.put = (args: { mapping: number | { id: number } } | [mapping: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateMapping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateMapping.form = updateMappingForm

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
* @see \App\Http\Controllers\ImportController::setQty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
export const setQty = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: setQty.url(args, options),
    method: 'post',
})

setQty.definition = {
    methods: ["post"],
    url: '/setup/import/records/{record}/qty',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::setQty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
setQty.url = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return setQty.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::setQty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
setQty.post = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: setQty.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::setQty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
const setQtyForm = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setQty.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::setQty
* @see app/Http/Controllers/ImportController.php:213
* @route '/setup/import/records/{record}/qty'
*/
setQtyForm.post = (args: { record: number | { id: number } } | [record: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setQty.url(args, options),
    method: 'post',
})

setQty.form = setQtyForm

/**
* @see \App\Http\Controllers\ImportController::ignoreUndecided
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
export const ignoreUndecided = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ignoreUndecided.url(options),
    method: 'post',
})

ignoreUndecided.definition = {
    methods: ["post"],
    url: '/setup/import/ignore-undecided',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::ignoreUndecided
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
ignoreUndecided.url = (options?: RouteQueryOptions) => {
    return ignoreUndecided.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::ignoreUndecided
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
ignoreUndecided.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ignoreUndecided.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::ignoreUndecided
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
const ignoreUndecidedForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ignoreUndecided.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::ignoreUndecided
* @see app/Http/Controllers/ImportController.php:203
* @route '/setup/import/ignore-undecided'
*/
ignoreUndecidedForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ignoreUndecided.url(options),
    method: 'post',
})

ignoreUndecided.form = ignoreUndecidedForm

const ImportController = { index, saveConnection, testConnection, forgetConnection, fetch, post, updateMapping, resolve, setQty, ignoreUndecided }

export default ImportController