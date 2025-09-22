import React from 'react';
import useAssessmentStatus from '@/hooks/useAssessmentStatus';
import { QueueBadge } from './QueueIndicator';

export default function SessionStatusBadge({ session }) {
  const { status: queueStatus } = useAssessmentStatus(
    session.status === 'completed' ? session.id : null,
    {
      enableSSE: false, // Only use polling for session list
      pollInterval: 10000, // Less frequent polling for list view
    }
  );

  // Show queue status if session is completed and has queue activity
  if (session.status === 'completed' && queueStatus) {
    return <QueueBadge status={queueStatus} className="ml-2" />;
  }

  return null;
}