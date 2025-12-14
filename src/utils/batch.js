export const batchRequests = async (items, requestFn, batchSize = 5) => {
    const results = [];
    for (let i = 0; i < items.length; i += batchSize) {
        const batch = items.slice(i, i + batchSize);
        const batchResults = await Promise.all(batch.map(item => requestFn(item)));
        results.push(...batchResults);
    }
    return results;
};

export const filterCategoriesByLang = (categories, lang) => {
    const frKeywords = ['fr', 'french', 'france', 'vf', 'vostfr'];
    const enKeywords = ['en', 'english', 'uk', 'us', 'usa', 'vo', 'series']; // 'series' often implies general/english if not specified, but risky. stick to explicit.

    // Strict english keywords for now
    const enKeywordsStrict = ['en', 'english', 'uk', 'us', 'usa'];

    const keywords = lang === 'fr' ? frKeywords : enKeywordsStrict;

    return categories.filter(cat => {
        const name = cat.category_name.toLowerCase();
        // Check if ANY keyword matches
        // We use word boundaries or straightforward inclusion depending on preference.
        // Simple inclusion is often enough for "FR | Action"
        return keywords.some(k => name.includes(k));
    });
};
