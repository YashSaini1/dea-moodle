import { TARGET_INPUT_ELEMENT_ID } from '@constants/constants';
import { IErrorEventData, ICatchEditorTextErrorParams } from '@projectTypes/interfaces';
import { setErrorMessage } from '@store/redux/stage/stageSlice';

export const catchEditorTextError = (params: ICatchEditorTextErrorParams) => {
  const { correctEditorText, sendButtonIds, error, dispatch } = params;

  const sendButton1 = document.getElementById(sendButtonIds[0]) as HTMLInputElement;
  const sendButton2 = document.getElementById(sendButtonIds[1]) as HTMLInputElement;

  const targetInput = document.getElementById(
    TARGET_INPUT_ELEMENT_ID,
  ) as HTMLInputElement;

  const startLine: number = error.location.start.line;
  const startColumn: number = error.location.start.column;

  const errorMessage = `(${startLine}:${startColumn}) ${error.message}`;

  const errorEvent = new CustomEvent<IErrorEventData>('editor_error', {
    bubbles: true,
    cancelable: true,
    detail: {
      column: startColumn - 1,
      row: startLine - 1,
      text: error.message,
      type: 'error',
    },
  });

  if (targetInput) {
    if (sendButton1) {
      sendButton1.setAttribute('disabled', 'disabled');
    }

    if (sendButton2) {
      sendButton2.setAttribute('disabled', 'disabled');
    }

    targetInput.value = correctEditorText;
  }

  dispatch(setErrorMessage(errorMessage));
  document.dispatchEvent(errorEvent);
};
