import client from './client';

/** The signed-in admin's tokens for the MCP server ([[McpTokenController]]). */
export const fetchMcpTokens = () => client.get('/admin/profile/mcp-tokens').then((r) => r.data.data);
export const createMcpToken = (name) => client.post('/admin/profile/mcp-tokens', { name }).then((r) => r.data);
export const revokeMcpToken = (id) => client.delete(`/admin/profile/mcp-tokens/${id}`);
