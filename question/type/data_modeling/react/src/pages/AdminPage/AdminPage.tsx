import React from 'react';

import '@scss/react/pages/adminPage.scss';
import { TablesWidget } from '@widgets/tablesWidget/TablesWidget';
import { ReactFlowProvider } from 'reactflow';

export const AdminPage = () => {
  const sendButtonId1 = 'id_updatebutton';
  const sendButtonId2 = 'id_submitbutton';

  const editorTextareaElement = document.getElementById(
    'id_answer',
  ) as HTMLTextAreaElement;

  return (
    <ReactFlowProvider>
      <TablesWidget
        containerClassName='AdminPageContainer'
        editorTextareaElement={editorTextareaElement}
        sendButtonIds={[sendButtonId1, sendButtonId2]}
      />
    </ReactFlowProvider>
  );
};
