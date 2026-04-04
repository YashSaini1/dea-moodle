import React from 'react';

import '@scss/react/pages/sqlPage.scss';
import { TablesWidget } from '@widgets/tablesWidget/TablesWidget';
import { ReactFlowProvider } from 'reactflow';

export const SqlPage = () => {
  const sendButtonId = 'mod_quiz-next-nav';

  const editorTextareaElement = document.querySelector(
    '.sqlrunner-answer.edit_code',
  ) as HTMLTextAreaElement;

  return (
    <ReactFlowProvider>
      <TablesWidget
        editorTextareaElement={editorTextareaElement}
        sendButtonIds={[sendButtonId]}
      />
    </ReactFlowProvider>
  );
};
