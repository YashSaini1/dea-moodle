import { Parser } from '@dbml/core';
import {
  EditorInputEvent,
  IErrorEventData,
  IAnalyzeEditorTextParams,
} from '@projectTypes/interfaces';
import { setEditorText, setErrorMessage } from '@store/redux/stage/stageSlice';
import { trim } from 'lodash';
import { nanoid } from 'nanoid';

import { catchEditorTextError } from './catchEditorTextError';

export const analyzeEditorText = (params: IAnalyzeEditorTextParams) => {
  const { sendButtonIds, event, dispatch, reducerDispatch } = params;

  const sendButton1 = document.getElementById(sendButtonIds[0]) as HTMLInputElement;
  const sendButton2 = document.getElementById(sendButtonIds[1]) as HTMLInputElement;

  try {
    if (sendButton1) {
      sendButton1.removeAttribute('disabled');
    }
    if (sendButton2) {
      sendButton2.removeAttribute('disabled');
    }

    const redeclaredEvent = event as EditorInputEvent;

    const parserResult = Parser.parse(redeclaredEvent.data, 'dbml');

    const trimmedEditorText = trim(redeclaredEvent.data);

    if (parserResult && trimmedEditorText.length > 0) {
      const emptyErrorEvent = new CustomEvent<IErrorEventData>('editor_error', {
        bubbles: true,
        cancelable: true,
        detail: {
          column: 0,
          row: 0,
          text: '',
          type: 'information',
        },
      });

      dispatch(setErrorMessage(''));
      dispatch(setEditorText(trimmedEditorText));

      reducerDispatch({ type: 'setUpdateButtonEventListener', payload: nanoid() });

      document.dispatchEvent(emptyErrorEvent);
    } else if (parserResult && trimmedEditorText.length === 0) {
      const emptyErrorEvent = new CustomEvent<IErrorEventData>('editor_error', {
        bubbles: true,
        cancelable: true,
        detail: {
          column: 0,
          row: 0,
          text: '',
          type: 'information',
        },
      });

      dispatch(setErrorMessage(''));
      dispatch(setEditorText(''));

      document.dispatchEvent(emptyErrorEvent);
    }
  } catch (error) {
    catchEditorTextError({ sendButtonIds, correctEditorText: '', error, dispatch });
  }
};
