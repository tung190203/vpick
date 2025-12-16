export function useTimeFormat() {
    const toHourMinute = (time) => {
      if (!time || typeof time !== 'string') return '';
      return time.slice(0, 5);
    };
    return { toHourMinute };
  }
  