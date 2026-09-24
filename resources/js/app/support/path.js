/**
 * Read and write a form value by its schema name — `title`, or `facts.0`, the
 * second of the three facts. What the server validates by the same path.
 */
export function get(object, path) {
	return path.split('.').reduce((value, key) => value?.[key], object);
}

export function set(object, path, value) {
	const keys = path.split('.');
	const last = keys.pop();
	const target = keys.reduce((value, key) => value[key], object);
	target[last] = value;
}
