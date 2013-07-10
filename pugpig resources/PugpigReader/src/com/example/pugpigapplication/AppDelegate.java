package com.example.pugpigapplication;

import com.kaldorgroup.pugpig.app.*;
import com.kaldorgroup.pugpig.net.*;
import com.kaldorgroup.pugpig.net.auth.*;
import com.kaldorgroup.pugpig.util.*;

import java.net.URL;

public class AppDelegate implements ApplicationDelegate {
  private boolean loading;
  public Authorisation singleAuth;
  public Authorisation subsAuth;

  @Override
  public void didFinishLaunching() {
    DocumentManager dm = DocumentManager.sharedManager();
    // TODO: Uncomment the line below if you want to automatically remove old documents.
    // dm.setMaximumDocuments(10);
    loading = dm.documents().size() == 0;

    // TODO: If you need to support paid content, this is where you set up your authorisation providers.
    // singleAuth = new AmazonAuthorisation("http://", "");
    // dm.addAuthorisation(singleAuth);
    // subsAuth = new AmazonSubscriptionAuthorisation("http://", "");
    // dm.addAuthorisation(subsAuth);

    dm.resumeDownloads();
    refreshDocuments();
  }

  @Override
  public void didBecomeActive() {
  }

  @Override
  public void willResignActive() {
  }

  @Override
  public void didEnterBackground() {
  }

  @Override
  public void willEnterForeground() {
    refreshDocuments();
  }

  public void refreshDocuments() {
    refreshDocumentsAndDownload(false);
  }

  public void refreshDocumentsAndDownload(final boolean download) {
    // TODO: Fill in your OPDS endpoint URL here.
    final URL url = URLUtils.URLWithString("");

    DocumentManager dm = DocumentManager.sharedManager();
    dm.resumeDownloads();
    dm.addDocumentsFromOPDSFeedURL(url, false, new RunnableWith<Integer>() {
      public void run(Integer added) {
        loading = false;
        ViewController topViewController = Application.topViewController();
        if (topViewController.getClass() == DocumentPickerViewController.class) ((DocumentPickerViewController)topViewController).refreshDocumentPicker();
        if (download) DocumentManager.sharedManager().downloadMostRecentDocument();
      }
    });

    Dispatch.cancelPreviousPerformRequestsWithSelector(this, "refreshDocuments", null);
    Dispatch.performSelectorAfterDelay(this, "refreshDocuments", null, 60 * 60);
  }

  public void openDocument(final Document document) {
    ViewLauncher launcher = ViewLauncher.launcherForClass(DocumentViewController.class);
    launcher.onLoad(new RunnableWith<DocumentViewController>() {
      public void run(DocumentViewController vc) {
        vc.openDocument(document);
      }
    });
    Application.topViewController().presentViewController(launcher);
  }

  public boolean isLoading() {
    return loading;
  }
}
