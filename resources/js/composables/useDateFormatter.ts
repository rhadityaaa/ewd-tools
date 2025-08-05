export function useDateFormatter() {
  const formatDate = (date: string | null | undefined): string => {
    if (!date) return 'N/A';
    
    try {
      return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch {
      return 'Invalid Date';
    }
  };

  return {
    formatDate
  };
}