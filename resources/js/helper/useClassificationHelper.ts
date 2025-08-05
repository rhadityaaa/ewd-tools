import { CheckCircle, AlertTriangle, Info } from 'lucide-vue-next';

export function useClassificationHelper() {
  const getClassificationColor = (classification: 'SAFE' | 'WATCHLIST'): string => {
    switch (classification) {
      case 'SAFE': return 'text-green-600';
      case 'WATCHLIST': return 'text-red-600';
      default: return 'text-gray-600';
    }
  };

  const getClassificationBg = (classification: 'SAFE' | 'WATCHLIST'): string => {
    switch (classification) {
      case 'SAFE': return 'bg-green-50 border-green-200';
      case 'WATCHLIST': return 'bg-red-50 border-red-200';
      default: return 'bg-gray-50 border-gray-200';
    }
  };

  const getClassificationIcon = (classification: 'SAFE' | 'WATCHLIST') => {
    switch (classification) {
      case 'SAFE': return CheckCircle;
      case 'WATCHLIST': return AlertTriangle;
      default: return Info;
    }
  };

  const getCollectibilityInfo = (value: number) => {
    const info: Record<number, { label: string; color: string; bg: string }> = {
      1: { label: '1 - Lancar', color: 'text-green-600', bg: 'bg-green-100' },
      2: { label: '2 - DPK', color: 'text-yellow-600', bg: 'bg-yellow-100' },
      3: { label: '3 - Kurang Lancar', color: 'text-orange-600', bg: 'bg-orange-100' },
      4: { label: '4 - Diragukan', color: 'text-red-600', bg: 'bg-red-100' },
      5: { label: '5 - Macet', color: 'text-red-700', bg: 'bg-red-200' }
    };
    return info[value] || { label: 'N/A', color: 'text-gray-600', bg: 'bg-gray-100' };
  };

  const getRiskLevelColor = (ratio: number): string => {
    if (ratio >= 90) return 'text-red-600';
    if (ratio >= 75) return 'text-orange-600';
    if (ratio >= 50) return 'text-yellow-600';
    return 'text-green-600';
  };

  return {
    getClassificationColor,
    getClassificationBg,
    getClassificationIcon,
    getCollectibilityInfo,
    getRiskLevelColor
  };
}